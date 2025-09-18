<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Collection;

class MinimumStockReportService
{
    /**
     * Get products with stock below minimum threshold
     */
    public function getProductsBelowMinStockCount(): int
    {
        return Product::query()
            ->whereHas('inventoryItem', function ($query) {
                $query->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)');
            })
            ->count();
    }

    /**
     * Get statistics for minimum stock report
     */
    public function getMinimumStockStats(): array
    {
        // Get total products count
        $totalProducts = Product::whereHas('inventoryItem')->count();

        // Get products below minimum stock
        $belowMinStockCount =Product::whereHas('inventoryItem', function ($query) {
            $query->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)');
        })->count();

        // Get products with zero stock
        $zeroStockCount = Product::whereHas('inventoryItem', function ($query) {
            $query->where('quantity', '<=', 0);
        })->count();

        // Get products with critical stock (less than half of min_stock)
        $criticalStockCount = Product::whereHas('inventoryItem', function ($query) {
            $query->whereRaw('quantity < (SELECT min_stock / 2 FROM products WHERE products.id = inventory_items.product_id)');
        })->count();

        // Calculate percentage of products below minimum stock
        $belowMinStockPercentage = $totalProducts > 0 ? ($belowMinStockCount / $totalProducts) * 100 : 0;



        return [
            'totalProducts' => $totalProducts,
            'belowMinStockCount' => $belowMinStockCount,
            'zeroStockCount' => $zeroStockCount,
            'criticalStockCount' => $criticalStockCount,
            'belowMinStockPercentage' => $belowMinStockPercentage,
        ];
    }
    /**
     * Get purchase recommendations for products below minimum stock
     */
    public function getPurchaseRecommendations(): array
    {
        // Calculate total value of products below minimum stock
        $totalValueBelowMinStock = Product::query()
            ->join('inventory_items', 'products.id', '=', 'inventory_items.product_id')
            ->whereRaw('inventory_items.quantity < products.min_stock')
            ->selectRaw('SUM(GREATEST(0, products.min_stock - inventory_items.quantity) * products.cost) as total_value')
            ->value('total_value') ?? 0;

        $zeroStockCount = Product::whereHas('inventoryItem', function ($query) {
            $query->where('quantity', '<=', 0);
        })->count();
        return [
            'zeroStockCount' => $zeroStockCount,
            'totalValueBelowMinStock' => $totalValueBelowMinStock,
        ];
    }

}
