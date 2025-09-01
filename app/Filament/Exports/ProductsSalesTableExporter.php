<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductsSalesTableExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('اسم المنتج'),

            ExportColumn::make('category_name')
                ->label('التصنيف')
                ->state(function ($record) {
                    return $record->category_name ?: 'غير مصنف';
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

            ExportColumn::make('dine_in_sales')
                ->label('صالة (ج.م)')
                ->state(function ($record) {
                    return number_format($record->dine_in_sales ?? 0, 2);
                }),

            ExportColumn::make('takeaway_sales')
                ->label('تيك أواي (ج.م)')
                ->state(function ($record) {
                    return number_format($record->takeaway_sales ?? 0, 2);
                }),

            ExportColumn::make('delivery_sales')
                ->label('دليفري (ج.م)')
                ->state(function ($record) {
                    return number_format($record->delivery_sales ?? 0, 2);
                }),

            ExportColumn::make('web_delivery_sales')
                ->label('اونلاين دليفري (ج.م)')
                ->state(function ($record) {
                    return number_format($record->web_delivery_sales ?? 0, 2);
                }),

            ExportColumn::make('web_takeaway_sales')
                ->label('اونلاين تيك أواي (ج.م)')
                ->state(function ($record) {
                    return number_format($record->web_takeaway_sales ?? 0, 2);
                }),

            ExportColumn::make('talabat_sales')
                ->label('طلبات (ج.م)')
                ->state(function ($record) {
                    return number_format($record->talabat_sales ?? 0, 2);
                }),

            // ExportColumn::make('companies_sales')
            //     ->label('شركات (ج.م)')
            //     ->state(function ($record) {
            //         return number_format($record->companies_sales ?? 0, 2);
            //     }),

            // Additional performance metrics
            ExportColumn::make('unit_price')
                ->label('سعر الوحدة (ج.م)')
                ->state(function ($record) {
                    return number_format($record->price ?? 0, 2);
                }),

            ExportColumn::make('unit_cost')
                ->label('تكلفة الوحدة (ج.م)')
                ->state(function ($record) {
                    return number_format($record->cost ?? 0, 2);
                }),

            ExportColumn::make('average_selling_price')
                ->label('متوسط سعر البيع (ج.م)')
                ->state(function ($record) {
                    $totalQuantity = $record->total_quantity ?? 0;
                    $totalSales = $record->total_sales ?? 0;
                    $avgPrice = $totalQuantity > 0 ? $totalSales / $totalQuantity : 0;
                    return number_format($avgPrice, 2);
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير تقرير أداء المنتجات وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'منتج' : 'منتج') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'منتج' : 'منتج') . '.';
        }

        return $body;
    }
}
