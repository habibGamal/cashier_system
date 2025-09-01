<?php

namespace App\Services;

use Exception;
use App\Enums\MovementReason;
use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Services\StockService;
use App\Services\ProductCostManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    protected StockService $stockService;
    protected ProductCostManagementService $costManagementService;

    public function __construct(
        StockService $stockService,
        ProductCostManagementService $costManagementService
    ) {
        $this->stockService = $stockService;
        $this->costManagementService = $costManagementService;
    }

    /**
     * Close a purchase invoice and add items to stock
     */
    public function closePurchaseInvoice(PurchaseInvoice $invoice): bool
    {
        if ($invoice->closed_at) {
            throw new Exception('هذه الفاتورة مغلقة بالفعل');
        }

        shouldDayBeOpen();

        try {
            DB::beginTransaction();

            // Get all items for this invoice
            $items = $invoice->items()->with('product')->get();

            if ($items->isEmpty()) {
                throw new Exception('لا توجد أصناف في هذه الفاتورة');
            }

            // Prepare items for stock processing
            $stockItems = $items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            // Update product costs using average cost strategy
            $this->costManagementService->updateProductCostsWithAverage($invoice->items);

            // Add items to stock
            $success = $this->stockService->addStock(
                $stockItems,
                MovementReason::PURCHASE,
                $invoice
            );

            if (!$success) {
                throw new Exception('فشل في إضافة الأصناف إلى المخزون');
            }

            // Mark invoice as closed
            $invoice->update(['closed_at' => now()]);

            DB::commit();

            Log::info("Purchase invoice {$invoice->id} closed successfully with cost updates");
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to close purchase invoice {$invoice->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Close a return purchase invoice and remove items from stock
     */
    public function closeReturnPurchaseInvoice(ReturnPurchaseInvoice $invoice): bool
    {
        if ($invoice->closed_at) {
            throw new Exception('هذه الفاتورة مغلقة بالفعل');
        }

        shouldDayBeOpen();

        try {
            DB::beginTransaction();

            // Get all items for this invoice
            $items = $invoice->items()->with('product')->get();

            if ($items->isEmpty()) {
                throw new Exception('لا توجد أصناف في هذه الفاتورة');
            }

            // Prepare items for stock processing and validate availability
            $stockItems = $items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            // Check stock availability
            $insufficientItems = $this->stockService->validateStockAvailability($stockItems);

            // if (!empty($insufficientItems)) {
            //     $errorMessage = 'مخزون غير كافي للمنتجات التالية: ';
            //     foreach ($insufficientItems as $item) {
            //         $errorMessage .= "\n- {$item['product_name']}: متوفر {$item['available_quantity']}, مطلوب {$item['required_quantity']}";
            //     }
            //     throw new \Exception($errorMessage);
            // }

            // Remove items from stock
            $success = $this->stockService->removeStock(
                $stockItems,
                MovementReason::PURCHASE_RETURN,
                $invoice
            );

            if (!$success) {
                throw new Exception('فشل في خصم الأصناف من المخزون');
            }

            // Mark invoice as closed
            $invoice->update(['closed_at' => now()]);

            DB::commit();

            Log::info("Return purchase invoice {$invoice->id} closed successfully");
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to close return purchase invoice {$invoice->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if invoice can be edited
     */
    public function canEditInvoice($invoice): bool
    {
        return is_null($invoice->closed_at);
    }

    /**
     * Check if invoice is closed
     */
    public function isInvoiceClosed($invoice): bool
    {
        return !is_null($invoice->closed_at);
    }
}
