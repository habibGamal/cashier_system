<?php

namespace App\Services;

use Exception;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCostManagementService
{
    /**
     * Update product costs using average cost strategy
     * Formula: new cost = (old cost * old quantity + new cost * new quantity) / total quantity
     *
     * @param  $purchaseItems
     * @return bool
     */
    public function updateProductCostsWithAverage($purchaseItems): bool
    {
        try {
            DB::beginTransaction();

            $purchaseItems->load('product.inventoryItem');

            foreach ($purchaseItems as $item) {
                $newQuantity = $item->quantity;
                $newCost = $item->price;
                $oldQuantity = $item->product->inventoryItem->quantity ?? 0;
                $oldQuantity = $oldQuantity < 0 ? 0 : $oldQuantity;
                $oldCost = $item->product->cost ?? 0;

                $newAverageCost = ($oldCost * $oldQuantity + $newCost * $newQuantity) / ($oldQuantity + $newQuantity);
                $item->product->update(['cost' => $newAverageCost]);
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update product costs: " . $e->getMessage());
            throw $e;
        }
    }

}
