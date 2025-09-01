<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockReportExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('المنتج'),

            ExportColumn::make('category.name')
                ->label('التصنيف'),

            ExportColumn::make('start_quantity')
                ->label('الكمية البدائية')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('incoming')
                ->label('كمية الوارد')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('return_sales')
                ->label('مرتجع المبيعات')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('total_quantity')
                ->label('الكمية الكلية')
                ->state(function ($record) {
                    return ($record->start_quantity ?? 0) + ($record->incoming ?? 0) + ($record->return_sales ?? 0);
                }),

            ExportColumn::make('sales')
                ->label('كمية المبيعات')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('return_waste')
                ->label('كمية الفاقد والمرتجع')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('total_consumed')
                ->label('الكمية الكلية المنصرفة')
                ->state(function ($record) {
                    return ($record->sales ?? 0) + ($record->return_waste ?? 0);
                }),

            ExportColumn::make('ideal_remaining')
                ->label('الكمية المتبقية المثالية')
                ->state(function ($record) {
                    $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0) + ($record->return_sales ?? 0);
                    $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                    return $totalQuantity - $totalConsumed;
                }),

            ExportColumn::make('actual_remaining_quantity')
                ->label('الكمية المتبقية الفعلية')
                ->formatStateUsing(fn ($state) => $state ?? 0),

            ExportColumn::make('average_cost')
                ->label('متوسط التكلفة (جنيه)')
                ->state(function ($record) {
                    return $record->cost ?? 0;
                })
                ->formatStateUsing(fn ($state) => number_format($state, 2)),

            ExportColumn::make('deviation')
                ->label('الانحراف')
                ->state(function ($record) {
                    $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0) + ($record->return_sales ?? 0);
                    $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                    $idealRemaining = $totalQuantity - $totalConsumed;
                    $actualRemaining = $record->actual_remaining_quantity ?? 0;
                    return $actualRemaining - $idealRemaining;
                }),

            ExportColumn::make('deviation_value')
                ->label('قيمة الانحراف (جنيه)')
                ->state(function ($record) {
                    $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0) + ($record->return_sales ?? 0);
                    $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                    $idealRemaining = $totalQuantity - $totalConsumed;
                    $actualRemaining = $record->actual_remaining_quantity ?? 0;
                    $deviation = $actualRemaining - $idealRemaining;
                    return abs($deviation) * ($record->cost ?? 0);
                })
                ->formatStateUsing(fn ($state) => number_format($state, 2)),

            ExportColumn::make('deviation_percentage')
                ->label('نسبة الانحراف (%)')
                ->state(function ($record) {
                    $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0) + ($record->return_sales ?? 0);
                    $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                    $idealRemaining = $totalQuantity - $totalConsumed;
                    $actualRemaining = $record->actual_remaining_quantity ?? 0;
                    $deviation = $actualRemaining - $idealRemaining;

                    if ($idealRemaining == 0) return 0;
                    return ($deviation / $idealRemaining) * 100;
                })
                ->formatStateUsing(fn ($state) => number_format($state, 1)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير تقرير المخزون وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'صف' : 'صفوف') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'صف' : 'صفوف') . '.';
        }

        return $body;
    }
}
