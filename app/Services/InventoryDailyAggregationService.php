<?php

namespace App\Services;

use Exception;
use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Models\Waste;
use App\Models\Stocktaking;
use App\Models\InventoryItemMovementDaily;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Enums\MovementReason;
use App\Enums\InventoryMovementOperation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryDailyAggregationService
{

    /**
     * Get the status of the current day
     * Returns the opened day date or null if all days are closed
     */
    public function dayStatus(): ?Carbon
    {
        $openDay = InventoryItemMovementDaily::whereNull('closed_at')
            ->orderBy('date', 'desc')
            ->first();

        return $openDay ? Carbon::parse($openDay->date) : null;
    }

    /**
     * Open a new day by creating InventoryItemMovementDaily records for all products
     * Clones the last day's end_quantity as start_quantity for today
     */
    public function openDay(): int
    {
        $today = Carbon::today();
        $dateString = $today->toDateString();
        return DB::transaction(function () use ($dateString, $today) {
            try {
                // Check if day is already opened
                $existingRecords = InventoryItemMovementDaily::where('date', $today)->count();
                if ($existingRecords > 0) {
                    // ensure that they not closed by closed_at
                    InventoryItemMovementDaily::where('date', $today)->update(['closed_at' => null]);
                    Log::info("Day {$dateString} is already opened");
                    return $existingRecords;
                }

                // Get all products that have inventory items
                $productsWithInventory = Product::whereHas('inventoryItem')->get();

                $insertedCount = 0;

                foreach ($productsWithInventory as $product) {
                    // Get the last daily movement record for this product
                    $lastRecord = InventoryItemMovementDaily::where('product_id', $product->id)
                        ->orderBy('date', 'desc')
                        ->first();

                    $startQuantity = 0;
                    if ($lastRecord) {
                        // Use end_quantity from last record or calculate it if not set
                        $startQuantity = $lastRecord->end_quantity;
                    } else {
                        // If no previous record, use current inventory quantity
                        $inventoryItem = $product->inventoryItem;
                        $startQuantity = $inventoryItem ? $inventoryItem->quantity : 0;
                    }

                    InventoryItemMovementDaily::create([
                        'product_id' => $product->id,
                        'date' => $today,
                        'start_quantity' => $startQuantity,
                        'incoming_quantity' => 0,
                        'return_sales_quantity' => 0,
                        'sales_quantity' => 0,
                        'return_waste_quantity' => 0,
                        'end_quantity' => $startQuantity,
                        'closed_at' => null,
                    ]);

                    $insertedCount++;
                }

                Log::info("Opened day for {$dateString}", [
                    'date' => $dateString,
                    'records_created' => $insertedCount
                ]);

                return $insertedCount;

            } catch (Exception $e) {
                Log::error("Failed to open day for {$dateString}", [
                    'error' => $e->getMessage(),
                    'date' => $dateString,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Close the current day by setting end_quantity and closed_at
     */
    public function closeDay(): int
    {
        if (app(ShiftService::class)->getCurrentShift() !== null) {
            throw new Exception('لا يمكن إغلاق اليوم أثناء وجود شيفت مفتوح');
        }

        // Check for open purchase invoices
        $openPurchases = PurchaseInvoice::whereNull('closed_at')->count();
        if ($openPurchases > 0) {
            throw new Exception("لا يمكن إغلاق اليوم لوجود {$openPurchases} فاتورة شراء مفتوحة");
        }

        // Check for open return purchase invoices
        $openReturnPurchases = ReturnPurchaseInvoice::whereNull('closed_at')->count();
        if ($openReturnPurchases > 0) {
            throw new Exception("لا يمكن إغلاق اليوم لوجود {$openReturnPurchases} فاتورة مرتجع شراء مفتوحة");
        }

        // Check for open wastes
        $openWastes = Waste::whereNull('closed_at')->count();
        if ($openWastes > 0) {
            throw new Exception("لا يمكن إغلاق اليوم لوجود {$openWastes} سجل هالك مفتوح");
        }

        // Check for open stocktaking
        $openStocktaking = Stocktaking::whereNull('closed_at')->count();
        if ($openStocktaking > 0) {
            throw new Exception("لا يمكن إغلاق اليوم لوجود {$openStocktaking} جرد مفتوح");
        }

        return DB::transaction(function () {
            try {
                // Get all open day records (where closed_at is null)
                $openRecords = InventoryItemMovementDaily::whereNull('closed_at')->get();

                if ($openRecords->isEmpty()) {
                    Log::info("No open day to close");
                    return 0;
                }

                $updatedCount = 0;
                $currentTime = now();

                foreach ($openRecords as $record) {
                    // Get current inventory quantity for this product
                    $inventoryItem = InventoryItem::where('product_id', $record->product_id)->first();
                    $currentQuantity = $inventoryItem ? $inventoryItem->quantity : 0;

                    // Update the record with end_quantity and closed_at
                    $record->update([
                        'end_quantity' => $currentQuantity,
                        'closed_at' => $currentTime,
                    ]);

                    $updatedCount++;
                }

                Log::info("Closed day", [
                    'records_updated' => $updatedCount,
                    'closed_at' => $currentTime
                ]);

                return $updatedCount;

            } catch (Exception $e) {
                Log::error("Failed to close day", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Aggregate movements for specified products using movements since the opened day
     */
    public function bulkAggregateWithInsertSelect(array $productIds): void
    {
        DB::transaction(function () use ($productIds) {
            try {
                // Get the last opened day date
                $openDay = InventoryItemMovementDaily::whereNull('closed_at')
                    ->orderBy('date', 'desc')
                    ->first();

                if (!$openDay) {
                    throw new Exception('No open day found. Please open a day first.');
                }

                $openDayDate = Carbon::parse($openDay->date);

                // Build the aggregation query for movements since the opened day
                $aggregationData = DB::table('inventory_item_movements as m')
                    ->select([
                        'm.product_id',
                        DB::raw("SUM(CASE
                            WHEN m.operation = 'in' AND m.reason IN ('purchase')
                            THEN m.quantity
                            ELSE 0
                        END) as incoming_quantity"),
                        DB::raw("SUM(
                            CASE
                                WHEN m.operation = 'in' AND m.reason IN ('order_return')
                                THEN m.quantity
                                ELSE 0
                            END
                        ) AS return_sales_quantity"),
                        DB::raw("SUM(CASE
                            WHEN m.operation = 'out' AND m.reason IN ('order')
                            THEN m.quantity
                            ELSE 0
                        END) as sales_quantity"),
                        DB::raw("SUM(CASE
                            WHEN (m.operation = 'out' AND m.reason IN ('waste', 'purchase_return'))
                            THEN m.quantity
                            ELSE 0
                        END) as return_waste_quantity"),
                    ])
                    ->whereIn('m.product_id', $productIds)
                    ->where('m.created_at', '>=', $openDay->created_at)
                    ->groupBy('m.product_id')
                    ->get()
                    ->keyBy('product_id');
                // Get existing open day records for the specified products
                $existingRecords = InventoryItemMovementDaily::whereIn('product_id', $productIds)
                    ->where('date', $openDayDate)
                    ->whereNull('closed_at')
                    ->get()
                    ->keyBy('product_id');

                // Update existing records
                foreach ($existingRecords as $productId => $record) {
                    $data = $aggregationData->get($productId);
                    if ($data) {
                        $record->update([
                            'incoming_quantity' => $data->incoming_quantity,
                            'return_sales_quantity' => $data->return_sales_quantity,
                            'sales_quantity' => $data->sales_quantity,
                            'return_waste_quantity' => $data->return_waste_quantity,
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Create records for products that don't have InventoryItemMovementDaily records
                // (products created after the day was opened)
                $existingProductIds = $existingRecords->keys()->toArray();
                $missingProductIds = array_diff($productIds, $existingProductIds);

                if (!empty($missingProductIds)) {
                    foreach ($missingProductIds as $productId) {
                        $data = $aggregationData->get($productId);

                        InventoryItemMovementDaily::create([
                            'product_id' => $productId,
                            'date' => $openDayDate,
                            'start_quantity' => 0, // New products start with 0
                            'incoming_quantity' => $data ? $data->incoming_quantity : 0,
                            'return_sales_quantity' => $data ? $data->return_sales_quantity : 0,
                            'sales_quantity' => $data ? $data->sales_quantity : 0,
                            'return_waste_quantity' => $data ? $data->return_waste_quantity : 0,
                            'end_quantity' => 0, // Will be updated when day is closed
                            'closed_at' => null,
                        ]);
                    }
                }

                Log::info("Bulk aggregated movements", [
                    'product_ids' => $productIds,
                    'open_day_date' => $openDayDate->toDateString(),
                    'updated_records' => $existingRecords->count(),
                    'created_records' => count($missingProductIds)
                ]);

            } catch (Exception $e) {
                Log::error("Failed to bulk aggregate movements", [
                    'error' => $e->getMessage(),
                    'product_ids' => $productIds,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Aggregate movements for multiple products and dates using the configured approach
     */
    public function aggregateMultipleMovements(array $productIds, Carbon $date)
    {
        return DB::transaction(function () use ($productIds, $date) {
            $dateString = $date->toDateString();

            try {
                $this->bulkAggregateWithInsertSelect($productIds);

            } catch (Exception $e) {
                Log::error("Failed to bulk aggregate movements for date {$dateString}", [
                    'error' => $e->getMessage(),
                    'product_ids' => $productIds,
                    'date' => $dateString,
                ]);
                throw $e;
            }
        });
    }
}
