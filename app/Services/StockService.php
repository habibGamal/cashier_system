<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use App\Models\Product;
use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use App\DTOs\StockMovementDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StockService
{
    /**
     * Setting to allow or disallow operations with insufficient stock
     * Set to false to prevent stock movements that would result in negative inventory
     */
    public const ALLOW_INSUFFICIENT_STOCK = true;

    private InventoryDailyAggregationService $dailyAggregationService;

    public function __construct(InventoryDailyAggregationService $dailyAggregationService)
    {
        // Ensure we're using the Query Builder approach for better compatibility
        $this->dailyAggregationService = $dailyAggregationService;
    }
    /**
     * Add stock for multiple items
     */
    public function addStock(array $items, MovementReason $reason = MovementReason::MANUAL, $referenceable = null): bool
    {
        return $this->processStock($items, InventoryMovementOperation::IN, $reason, $referenceable);
    }

    /**
     * Remove stock for multiple items
     */
    public function removeStock(array $items, MovementReason $reason = MovementReason::MANUAL, $referenceable = null): bool
    {
        return $this->processStock($items, InventoryMovementOperation::OUT, $reason, $referenceable);
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use addStock() or removeStock() instead
     */
    public function processMultipleItems(array $items, string $operation = 'add', string|MovementReason $reason = 'bulk_operation', $referenceable = null): bool
    {
        $movementOperation = $operation === 'add' ? InventoryMovementOperation::IN : InventoryMovementOperation::OUT;
        $movementReason = $reason instanceof MovementReason
            ? $reason
            : (MovementReason::tryFrom($reason) ?? MovementReason::MANUAL);

        return $this->processStock($items, $movementOperation, $movementReason, $referenceable);
    }

    /**
     * Internal method that processes stock movements
     */
    private function processStock(array $items, InventoryMovementOperation $operation, MovementReason $reason, $referenceable): bool
    {
        try {
            DB::beginTransaction();

            $this->validateItems($items);
            $this->validateProducts($items);

            if ($operation->isOutgoing()) {
                $this->validateStockAvailabilityOrThrow($items);
            }

            $existingInventory = $this->getExistingInventory($items);
            $stockMovements = $this->prepareStockMovements($items, $operation, $reason, $referenceable, $existingInventory);

            $this->executeStockMovements($stockMovements);
            $this->recordMovements($stockMovements);
            $this->updateDailyAggregations($stockMovements);
            // $this->logOperation($items, operation: $operation, $reason);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to process stock movement: " . $e->getMessage(), [
                'operation' => $operation->value,
                'reason' => $reason->value,
                'items_count' => count($items)
            ]);
            return false;
        }
    }

    /**
     * Validate items array structure
     */
    private function validateItems(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Items array cannot be empty');
        }

        foreach ($items as $index => $item) {
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                throw new InvalidArgumentException("Item at index {$index} must have 'product_id' and 'quantity' keys");
            }

            if (!is_numeric($item['product_id']) || !is_numeric($item['quantity'])) {
                throw new InvalidArgumentException("Item at index {$index} must have numeric 'product_id' and 'quantity' values");
            }

            if ($item['quantity'] <= 0) {
                throw new InvalidArgumentException("Item at index {$index} must have positive quantity");
            }
        }
    }

    /**
     * Validate that all products exist
     */
    private function validateProducts(array $items): void
    {
        $productIds = array_column($items, 'product_id');
        $existingProducts = Product::whereIn('id', $productIds)->pluck('id')->toArray();
        $missingProducts = array_diff($productIds, $existingProducts);

        if (!empty($missingProducts)) {
            throw new InvalidArgumentException("Products not found: " . implode(', ', $missingProducts));
        }
    }

    /**
     * Validate stock availability and throw exception if insufficient
     */
    private function validateStockAvailabilityOrThrow(array $items): void
    {
        // Skip validation if insufficient stock operations are allowed
        if (self::ALLOW_INSUFFICIENT_STOCK) {
            return;
        }

        $insufficientItems = $this->validateStockAvailability($items);

        if (!empty($insufficientItems)) {
            $errorMessage = "Insufficient stock for: " .
                implode(', ', array_column($insufficientItems, 'product_name'));
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Get existing inventory items
     */
    private function getExistingInventory(array $items): Collection
    {
        $productIds = array_column($items, 'product_id');

        return InventoryItem::whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Prepare stock movement DTOs
     */
    private function prepareStockMovements(
        array $items,
        InventoryMovementOperation $operation,
        MovementReason $reason,
        $referenceable,
        Collection $existingInventory
    ): array {
        $movements = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $existingItem = $existingInventory->get($productId);

            $movements[] = new StockMovementDTO(
                productId: $productId,
                quantity: $quantity,
                operation: $operation,
                reason: $reason,
                referenceable: $referenceable,
                existingInventoryItem: $existingItem
            );
        }

        return $movements;
    }

    /**
     * Execute stock movements (updates and inserts)
     */
    private function executeStockMovements(array $stockMovements): void
    {
        $inventoryUpdates = [];
        $newInventoryItems = [];

        /** @var StockMovementDTO $movement */
        foreach ($stockMovements as $movement) {
            if ($movement->existingInventoryItem) {
                $inventoryUpdates[] = $movement;
            } elseif ($movement->operation->isIncoming()) {
                $newInventoryItems[] = $movement;
            }
        }

        $this->updateExistingInventory($inventoryUpdates);
        $this->createNewInventoryItems($newInventoryItems);
    }

    /**
     * Update existing inventory items
     */
    private function updateExistingInventory(array $movements): void
    {
        if (empty($movements)) {
            return;
        }

        /** @var StockMovementDTO $movement */
        foreach ($movements as $movement) {
            $currentQuantity = $movement->existingInventoryItem->quantity;
            $newQuantity = $movement->operation->isIncoming()
                ? $currentQuantity + $movement->quantity
                : $currentQuantity - $movement->quantity;

            InventoryItem::where('id', $movement->existingInventoryItem->id)
                ->update(['quantity' => $newQuantity, 'updated_at' => now()]);
        }
    }

    /**
     * Create new inventory items
     */
    private function createNewInventoryItems(array $movements): void
    {
        if (empty($movements)) {
            return;
        }

        $items = [];
        /** @var StockMovementDTO $movement */
        foreach ($movements as $movement) {
            $items[] = [
                'product_id' => $movement->productId,
                'quantity' => $movement->quantity,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        InventoryItem::insert($items);
    }

    /**
     * Record inventory movements
     */
    private function recordMovements(array $stockMovements): void
    {
        if (empty($stockMovements)) {
            return;
        }

        $movements = [];
        /** @var StockMovementDTO $movement */
        foreach ($stockMovements as $movement) {
            $movements[] = [
                'product_id' => $movement->productId,
                'operation' => $movement->operation->value,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason->value,
                'referenceable_type' => $movement->referenceable ? get_class($movement->referenceable) : null,
                'referenceable_id' => $movement->referenceable?->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        InventoryItemMovement::insert($movements);
    }

    /**
     * Update daily aggregations for the affected products (optimized)
     */
    private function updateDailyAggregations(array $stockMovements): void
    {
        try {
            $today = Carbon::today();
            $productIds = array_unique(array: array_map(fn($movement) => $movement->productId, $stockMovements));

            // Then aggregate movements for better performance
            $this->dailyAggregationService->aggregateMultipleMovements($productIds, $today);

        } catch (Exception $e) {
            // Log error but don't fail the transaction as daily aggregation is not critical
            Log::error("Failed to update daily aggregations: " . $e->getMessage(), [
                'product_ids' => array_unique(array: array_map(fn($movement) => $movement->productId, $stockMovements))
            ]);
        }
    }




    /**
     * Log the operation
     */
    private function logOperation(array $items, InventoryMovementOperation $operation, MovementReason $reason): void
    {
        $totalItems = count($items);
        $operationType = $operation->isIncoming() ? 'add' : 'remove';

        Log::info("Bulk stock {$operationType}: {$totalItems} items processed", [
            'operation' => $operation->value,
            'reason' => $reason->value,
            'items_count' => $totalItems
        ]);
    }

    /**
     * Get current stock for a product
     */
    public function getCurrentStock(int $productId): float
    {
        $inventoryItem = InventoryItem::where('product_id', $productId)->first();
        return $inventoryItem ? $inventoryItem->quantity : 0;
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasSufficientStock(int $productId, float $requiredQuantity): bool
    {
        return $this->getCurrentStock($productId) >= $requiredQuantity;
    }

    /**
     * Validate stock availability for multiple items using bulk operations
     */
    public function validateStockAvailability(array $items): array
    {
        $productIds = array_column($items, 'product_id');

        // Get all inventory items for the products in a single query
        $inventoryItems = InventoryItem::whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Get product names for insufficient items in a single query
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $insufficientItems = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $requiredQuantity = $item['quantity'];

            $availableQuantity = $inventoryItems->has($productId)
                ? $inventoryItems[$productId]->quantity
                : 0;

            if ($availableQuantity < $requiredQuantity) {
                $product = $products->get($productId);
                $insufficientItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product ? $product->name : 'Unknown',
                    'required_quantity' => $requiredQuantity,
                    'available_quantity' => $availableQuantity
                ];
            }
        }

        return $insufficientItems;
    }
}
