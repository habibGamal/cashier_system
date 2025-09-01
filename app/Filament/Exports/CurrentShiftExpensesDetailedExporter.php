<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CurrentShiftExpensesDetailedExporter extends Exporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('amount')
                ->label('المبلغ (جنيه)')
                ->formatStateUsing(fn($state) => number_format((float) $state, 2)),

            ExportColumn::make('expenceType.name')
                ->label('نوع المصروف'),

            ExportColumn::make('notes')
                ->label('الملاحظات')
                ->formatStateUsing(fn($state) => $state ?: 'لا توجد ملاحظات'),

            ExportColumn::make('shift.start_at')
                ->label('بداية الشفت')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i:s') ?? ''),

            ExportColumn::make('created_at')
                ->label('وقت الإنشاء')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i:s') ?? ''),

            ExportColumn::make('updated_at')
                ->label('آخر تحديث')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i:s') ?? ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير تفاصيل مصروفات الشفت الحالي وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'مصروف' : 'مصروف') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'مصروف' : 'مصروف') . '.';
        }

        return $body;
    }
}
