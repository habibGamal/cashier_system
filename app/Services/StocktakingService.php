<?php

namespace App\Services;

use App\Models\Stocktaking;
use App\Enums\MovementReason;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StocktakingService
{
    private StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Close the stocktaking and update inventory quantities using StockService
     */
    public function closeStocktaking(Stocktaking $stocktaking): void
    {
        if ($stocktaking->closed_at) {
            throw new Exception('الجرد مغلق بالفعل');
        }


        shouldDayBeOpen();

        DB::beginTransaction();

        try {
            // Load stocktaking items with products
            $stocktaking->load(['items.product']);

            // Group items by variance type for bulk operations
            $itemsToAdd = [];
            $itemsToRemove = [];

            foreach ($stocktaking->items as $item) {
                $currentStock = $this->stockService->getCurrentStock($item->product_id);
                $realQuantity = $item->real_quantity;
                $variance = $realQuantity - $currentStock;

                if ($variance > 0) {
                    // Need to add stock
                    $itemsToAdd[] = [
                        'product_id' => $item->product_id,
                        'quantity' => $variance
                    ];
                } elseif ($variance < 0) {
                    // Need to remove stock
                    $itemsToRemove[] = [
                        'product_id' => $item->product_id,
                        'quantity' => abs($variance)
                    ];
                }
            }

            // Perform bulk stock operations
            if (!empty($itemsToAdd)) {
                $this->stockService->addStock($itemsToAdd, MovementReason::STOCKTAKING, $stocktaking);
                Log::info("Added stock for " . count($itemsToAdd) . " products in stocktaking {$stocktaking->id}");
            }

            if (!empty($itemsToRemove)) {
                $this->stockService->removeStock($itemsToRemove, MovementReason::STOCKTAKING, $stocktaking);
                Log::info("Removed stock for " . count($itemsToRemove) . " products in stocktaking {$stocktaking->id}");
            }

            // Mark stocktaking as closed
            $stocktaking->update([
                'closed_at' => now(),
                'total' => $stocktaking->items->sum('total')
            ]);

            DB::commit();

            Log::info("Stocktaking {$stocktaking->id} closed successfully with " .
                     (count($itemsToAdd) + count($itemsToRemove)) . " inventory adjustments");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to close stocktaking {$stocktaking->id}: " . $e->getMessage());
            throw new Exception('فشل في إغلاق الجرد: ' . $e->getMessage());
        }
    }
}
