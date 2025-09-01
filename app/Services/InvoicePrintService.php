<?php

namespace App\Services;

use InvalidArgumentException;
use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Models\Stocktaking;
use App\Models\Waste;

class InvoicePrintService
{
    /**
     * Get data for printing purchase invoice
     */
    public function getPurchaseInvoiceData(PurchaseInvoice $invoice): array
    {
        $invoice->load(['supplier', 'user', 'items.product']);

        return [
            'type' => 'purchase_invoice',
            'title' => 'فاتورة شراء',
            'id' => $invoice->id,
            'supplier' => $invoice->supplier?->name,
            'user' => $invoice->user?->name,
            'total' => $invoice->total,
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at->format('d/m/Y H:i'),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name ?? $item->product?->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price,
                ];
            }),
            'additional_info' => [
                ['label' => 'المورد', 'value' => $invoice->supplier?->name],
                ['label' => 'الهاتف', 'value' => $invoice->supplier?->phone],
                ['label' => 'العنوان', 'value' => $invoice->supplier?->address],
            ],
        ];
    }

    /**
     * Get data for printing return purchase invoice
     */
    public function getReturnPurchaseInvoiceData(ReturnPurchaseInvoice $invoice): array
    {
        $invoice->load(['supplier', 'user', 'items.product']);

        return [
            'type' => 'return_purchase_invoice',
            'title' => 'فاتورة مرتجع شراء',
            'id' => $invoice->id,
            'supplier' => $invoice->supplier?->name,
            'user' => $invoice->user?->name,
            'total' => $invoice->total,
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at->format('d/m/Y H:i'),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name ?? $item->product?->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price,
                ];
            }),
            'additional_info' => [
                ['label' => 'المورد', 'value' => $invoice->supplier?->name],
                ['label' => 'الهاتف', 'value' => $invoice->supplier?->phone],
                ['label' => 'العنوان', 'value' => $invoice->supplier?->address],
            ],
        ];
    }

    /**
     * Get data for printing stocktaking
     */
    public function getStocktakingData(Stocktaking $stocktaking): array
    {
        $stocktaking->load(['user', 'items.product']);

        return [
            'type' => 'stocktaking',
            'title' => 'جرد المخزون',
            'id' => $stocktaking->id,
            'user' => $stocktaking->user?->name,
            'total' => $stocktaking->total,
            'notes' => $stocktaking->notes,
            'created_at' => $stocktaking->created_at->format('d/m/Y H:i'),
            'items' => $stocktaking->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name ?? $item->product?->name,
                    'stock_quantity' => $item->stock_quantity,
                    'real_quantity' => $item->real_quantity,
                    'difference' => $item->real_quantity - $item->stock_quantity,
                    'price' => $item->price,
                    'total' => ($item->real_quantity - $item->stock_quantity) * $item->price,
                ];
            }),
            'additional_info' => [
                ['label' => 'إجمالي الفرق', 'value' => number_format($stocktaking->total, 2) . ' ج.م'],
            ],
        ];
    }

    /**
     * Get data for printing waste
     */
    public function getWasteData(Waste $waste): array
    {
        $waste->load(['user', 'items.product']);

        return [
            'type' => 'waste',
            'title' => 'سجل التالف',
            'id' => $waste->id,
            'user' => $waste->user?->name,
            'total' => $waste->total,
            'notes' => $waste->notes,
            'created_at' => $waste->created_at->format('d/m/Y H:i'),
            'items' => $waste->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name ?? $item->product?->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price,
                ];
            }),
            'additional_info' => [
                ['label' => 'إجمالي قيمة التالف', 'value' => number_format($waste->total, 2) . ' ج.م'],
            ],
        ];
    }

    /**
     * Get print data by type and ID
     */
    public function getPrintData(string $type, int $id): array
    {
        return match ($type) {
            'purchase_invoice' => $this->getPurchaseInvoiceData(PurchaseInvoice::findOrFail($id)),
            'return_purchase_invoice' => $this->getReturnPurchaseInvoiceData(ReturnPurchaseInvoice::findOrFail($id)),
            'stocktaking' => $this->getStocktakingData(Stocktaking::findOrFail($id)),
            'waste' => $this->getWasteData(Waste::findOrFail($id)),
            default => throw new InvalidArgumentException("Invalid print type: {$type}"),
        };
    }
}
