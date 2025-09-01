<?php

namespace App\Services\Resources;

class StocktakingCalculatorService
{
    /**
     * Calculate total for a single stocktaking item
     * total = (real_quantity - stock_quantity) * price
     */
    public static function calculateItemTotal(float $stockQuantity, float $realQuantity, float $price): float
    {
        return ($realQuantity - $stockQuantity) * $price;
    }

    /**
     * Calculate total for all items in a stocktaking from array
     */
    private static function calculateStocktakingTotalFromArray(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $stockQuantity = (float) ($item['stock_quantity'] ?? 0);
            $realQuantity = (float) ($item['real_quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $total += self::calculateItemTotal($stockQuantity, $realQuantity, $price);
        }

        return $total;
    }

    /**
     * Calculate total from stocktaking items collection
     */
    private static function calculateStocktakingTotalFromCollection($items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $stockQuantity = (float) ($item->stock_quantity ?? 0);
            $realQuantity = (float) ($item->real_quantity ?? 0);
            $price = (float) ($item->price ?? 0);
            $total += self::calculateItemTotal($stockQuantity, $realQuantity, $price);
        }

        return $total;
    }

    /**
     * Calculate total for all items in a stocktaking
     */
    public static function calculateStocktakingTotal($items): float
    {
        if (is_array($items)) {
            return self::calculateStocktakingTotalFromArray($items);
        }

        return self::calculateStocktakingTotalFromCollection($items);
    }

    /**
     * Prepare item data with calculated total
     */
    public static function prepareItemData(array $data): array
    {
        $stockQuantity = (float) ($data['stock_quantity'] ?? 0);
        $realQuantity = (float) ($data['real_quantity'] ?? 0);
        $price = (float) ($data['price'] ?? 0);

        $data['total'] = self::calculateItemTotal($stockQuantity, $realQuantity, $price);

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
                \$wire.data.total = items.reduce((total, item) => {
                    const stockQty = parseFloat(item.stock_quantity) || 0;
                    const realQty = parseFloat(item.real_quantity) || 0;
                    const price = parseFloat(item.price) || 0;
                    return total + ((realQty - stockQty) * price);
                }, 0);
                items.forEach(item => {
                    const stockQty = parseFloat(item.stock_quantity) || 0;
                    const realQty = parseFloat(item.real_quantity) || 0;
                    const price = parseFloat(item.price) || 0;
                    item.total = (realQty - stockQty) * price;
                });
            })
        JS;
    }
}
