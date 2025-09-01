<?php

namespace App\Services\Resources;

class PurchaseInvoiceCalculatorService
{
    /**
     * Calculate total for a single purchase invoice item
     */
    public static function calculateItemTotal(float $quantity, float $price): float
    {
        return $quantity * $price;
    }

    /**
     * Calculate total for all items in a purchase invoice
     */
    private static function calculateInvoiceTotalFromArray(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $total += self::calculateItemTotal($quantity, $price);
        }

        return $total;
    }

    /**
     * Calculate total from purchase invoice items collection
     */
    private static function calculateInvoiceTotalFromCollection($items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $quantity = (float) ($item->quantity ?? 0);
            $price = (float) ($item->price ?? 0);
            $total += self::calculateItemTotal($quantity, $price);
        }

        return $total;
    }


    /**
     * Calculate total for all items in a purchase invoice
     */
    public static function calculateInvoiceTotal($items): float
    {

        if (is_array($items)) {
            return self::calculateInvoiceTotalFromArray($items);
        }

        return self::calculateInvoiceTotalFromCollection($items);
    }

    /**
     * Prepare item data with calculated total
     */
    public static function prepareItemData(array $data): array
    {
        $quantity = (float) ($data['quantity'] ?? 0);
        $price = (float) ($data['price'] ?? 0);

        $data['total'] = self::calculateItemTotal($quantity, $price);

        return $data;
    }

    /**
     * Generate JavaScript code for frontend total calculation
     */
    public static function getJavaScriptCalculation(): string
    {
        return <<<JS
            \$watch('\$wire.data', value => {
                let items = \$wire.data.items;
                if (!Array.isArray(items)) {
                    items = Object.values(items);
                }
                \$wire.data.total = items.reduce((total, item) => total + (item.quantity * item.price || 0), 0).toFixed(2);
                items.forEach(item => {
                    item.total = ((item.quantity * item.price) || 0).toFixed(2);
                });
            })
        JS;
    }
}
