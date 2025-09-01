<?php

namespace App\Filament\Exports;

use App\Models\Category;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CategoryPerformanceExporter extends Exporter
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('category_name')
                ->label('التصنيف')
                ->state(function ($record) {
                    return $record->category_name ?: 'غير مصنف';
                }),

            ExportColumn::make('products_count')
                ->label('عدد المنتجات')
                ->state(function ($record) {
                    return $record->products_count ?? 0;
                }),

            ExportColumn::make('total_quantity')
                ->label('إجمالي الكمية')
                ->state(function ($record) {
                    return $record->total_quantity ?? 0;
                }),

            ExportColumn::make('total_sales')
                ->label('إجمالي المبيعات (ج.م)')
                ->state(function ($record) {
                    return number_format($record->total_sales ?? 0, 2);
                }),

            ExportColumn::make('total_profit')
                ->label('إجمالي الربح (ج.م)')
                ->state(function ($record) {
                    return number_format($record->total_profit ?? 0, 2);
                }),

            ExportColumn::make('profit_margin')
                ->label('هامش الربح %')
                ->state(function ($record) {
                    $totalSales = $record->total_sales ?? 0;
                    $totalProfit = $record->total_profit ?? 0;
                    return $totalSales > 0 ? number_format(($totalProfit / $totalSales) * 100, 1) . '%' : '0%';
                }),

            ExportColumn::make('avg_sales_per_product')
                ->label('متوسط المبيعات/منتج (ج.م)')
                ->state(function ($record) {
                    $quantity = $record->total_quantity ?? 0;
                    $totalSales = $record->total_sales ?? 0;
                    $avg = $quantity > 0 ? $totalSales / $quantity : 0;
                    return number_format($avg, 2);
                }),

            ExportColumn::make('avg_profit_per_product')
                ->label('متوسط الربح/منتج (ج.م)')
                ->state(function ($record) {
                    $quantity = $record->total_quantity ?? 0;
                    $totalProfit = $record->total_profit ?? 0;
                    $avg = $quantity > 0 ? $totalProfit / $quantity : 0;
                    return number_format($avg, 2);
                }),

            ExportColumn::make('avg_quantity_per_product')
                ->label('متوسط الكمية/منتج')
                ->state(function ($record) {
                    $productsCount = $record->products_count ?? 0;
                    $totalQuantity = $record->total_quantity ?? 0;
                    $avg = $productsCount > 0 ? $totalQuantity / $productsCount : 0;
                    return number_format($avg, 1);
                }),

            ExportColumn::make('category_contribution')
                ->label('مساهمة التصنيف في الإجمالي %')
                ->state(function ($record) {
                    // This will be calculated from all categories combined
                    // For now, we'll leave it as placeholder that can be calculated on report level
                    return 'يحسب في التقرير الشامل';
                }),

            ExportColumn::make('performance_rating')
                ->label('تقييم الأداء')
                ->state(function ($record) {
                    $totalSales = $record->total_sales ?? 0;
                    $profitMargin = 0;
                    if ($totalSales > 0) {
                        $profitMargin = (($record->total_profit ?? 0) / $totalSales) * 100;
                    }

                    if ($totalSales >= 10000 && $profitMargin >= 30) {
                        return 'ممتاز';
                    } elseif ($totalSales >= 5000 && $profitMargin >= 20) {
                        return 'جيد جداً';
                    } elseif ($totalSales >= 2000 && $profitMargin >= 15) {
                        return 'جيد';
                    } elseif ($totalSales >= 500 && $profitMargin >= 10) {
                        return 'مقبول';
                    } else {
                        return 'ضعيف';
                    }
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير تقرير أداء التصنيفات وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'تصنيف' : 'تصنيف') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'تصنيف' : 'تصنيف') . '.';
        }

        return $body;
    }
}
