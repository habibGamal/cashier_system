<?php

namespace App\Services\Orders;

use Exception;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Enums\ProductType;
use App\Services\StockService;
use App\Enums\MovementReason;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderStockConversionService
{
    public function __construct(
        private readonly StockService $stockService
    ) {
    }

    /**
     * Convert order items to stock items for stock reduction
     * Manufactured items are broken down into their components
     */
    public function convertOrderItemsToStockItems(Order $order): array
    {
        $stockItems = [];

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;

            switch ($product->type) {
                case ProductType::Manufactured:
                    // Break down manufactured product into its components
                    $componentItems = $this->getManufacturedProductComponents($product, $orderItem->quantity);
                    $stockItems = array_merge($stockItems, $componentItems);
                    break;

                case ProductType::Consumable:
                    // Add consumable item directly
                    $stockItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $orderItem->quantity,
                    ];
                    break;

                case ProductType::RawMaterial:
                    // Raw materials can't be sold directly in orders
                    Log::warning("Raw material found in order items", [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name
                    ]);
                    break;
            }
        }

        // Optimize by summing up common components
        return $this->optimizeStockItems($stockItems);
    }

    /**
     * Get components of a manufactured product with their required quantities
     */
    private function getManufacturedProductComponents(Product $manufacturedProduct, float $orderQuantity): array
    {
        $components = [];

        // Get all components for this manufactured product
        $productComponents = ProductComponent::where('product_id', $manufacturedProduct->id)
            ->with('component')
            ->get();

        foreach ($productComponents as $productComponent) {
            $component = $productComponent->component;
            $requiredQuantity = $productComponent->quantity * $orderQuantity;

            // Only include raw materials and consumables (not other manufactured products)
            if (in_array($component->type, [ProductType::RawMaterial, ProductType::Consumable])) {
                $components[] = [
                    'product_id' => $component->id,
                    'quantity' => $requiredQuantity,
                ];
            } else if ($component->type === ProductType::Manufactured) {
                // Recursively break down nested manufactured products
                $nestedComponents = $this->getManufacturedProductComponents($component, $requiredQuantity);
                $components = array_merge($components, $nestedComponents);
            }
        }

        return $components;
    }

    /**
     * Optimize stock items by summing up quantities for the same product
     */
    private function optimizeStockItems(array $stockItems): array
    {
        $optimized = [];

        foreach ($stockItems as $item) {
            $productId = $item['product_id'];

            if (isset($optimized[$productId])) {
                $optimized[$productId]['quantity'] += $item['quantity'];
            } else {
                $optimized[$productId] = $item;
            }
        }

        return array_values($optimized);
    }

    /**
     * Remove stock items when order is completed
     */
    public function removeStockForCompletedOrder(Order $order): bool
    {
        try {
            $stockItems = $this->convertOrderItemsToStockItems($order);

            if (empty($stockItems)) {
                return true;
            }

            return $this->stockService->removeStock(
                $stockItems,
                MovementReason::ORDER,
                $order
            );

        } catch (Exception $e) {
            Log::error("Failed to remove stock for completed order", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Add stock items back when order is cancelled after completion
     */
    public function addStockForCancelledOrder(Order $order): bool
    {
        try {
            $stockItems = $this->convertOrderItemsToStockItems($order);

            if (empty($stockItems)) {
                return true;
            }

            return $this->stockService->addStock(
                $stockItems,
                MovementReason::ORDER_RETURN,
                $order
            );

        } catch (Exception $e) {
            Log::error("Failed to add stock back for cancelled order", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate if order can be completed based on stock availability
     */
    public function validateOrderStockAvailability(Order $order): array
    {
        $stockItems = $this->convertOrderItemsToStockItems($order);

        if (empty($stockItems)) {
            return [];
        }

        return $this->stockService->validateStockAvailability($stockItems);
    }

    /**
     * Get stock requirements summary for an order
     */
    public function getOrderStockRequirements(Order $order): array
    {
        $stockItems = $this->convertOrderItemsToStockItems($order);
        $requirements = [];

        if (empty($stockItems)) {
            return $requirements;
        }

        $productIds = array_column($stockItems, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($stockItems as $item) {
            $product = $products->get($item['product_id']);
            if ($product) {
                $currentStock = $this->stockService->getCurrentStock($product->id);
                $requirements[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'required_quantity' => $item['quantity'],
                    'current_stock' => $currentStock,
                    'sufficient' => $currentStock >= $item['quantity'],
                ];
            }
        }

        return $requirements;
    }
}
