<?php

namespace App\Services;

use App\Models\Waste;
use App\Enums\MovementReason;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WasteService
{
    private StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Close the waste record and remove stock from inventory
     */
    public function closeWaste(Waste $waste): void
    {
        if ($waste->closed_at) {
            throw new Exception('سجل التالف مغلق بالفعل');
        }

        shouldDayBeOpen();

        DB::beginTransaction();

        try {
            // Load waste items with products
            $waste->load(['items.product']);

            // Prepare items for stock removal
            $itemsToRemove = [];
            foreach ($waste->items as $item) {
                $itemsToRemove[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity
                ];
            }

            // Remove stock for all wasted items
            if (!empty($itemsToRemove)) {
                $this->stockService->removeStock($itemsToRemove, MovementReason::WASTE, $waste);
                Log::info("Removed stock for " . count($itemsToRemove) . " products in waste {$waste->id}");
            }

            // Mark waste as closed
            $waste->update([
                'closed_at' => now(),
                'total' => $waste->items->sum('total')
            ]);

            DB::commit();

            Log::info("Waste {$waste->id} closed successfully with " . count($itemsToRemove) . " items removed from inventory");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to close waste {$waste->id}: " . $e->getMessage());
            throw new Exception('فشل في إغلاق سجل التالف: ' . $e->getMessage());
        }
    }
}
