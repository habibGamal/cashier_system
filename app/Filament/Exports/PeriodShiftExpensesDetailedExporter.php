<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PeriodShiftExpensesDetailedExporter extends Exporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('رقم المصروف'),

            ExportColumn::make('amount')
                ->label('المبلغ')
                ->formatStateUsing(fn($state) => (float) $state),

            ExportColumn::make('expenceType.name')
                ->label('نوع المصروف'),

            ExportColumn::make('notes')
                ->label('الملاحظات')
                ->formatStateUsing(fn($state) => $state ?? 'لا توجد ملاحظات'),

            ExportColumn::make('shift.id')
                ->label('رقم الشفت'),

            ExportColumn::make('shift.start_at')
                ->label('تاريخ بداية الشفت')
                ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i:s') : ''),

            ExportColumn::make('shift.end_at')
                ->label('تاريخ نهاية الشفت')
                ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i:s') : 'مفتوح'),

            ExportColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),

            ExportColumn::make('updated_at')
                ->label('تاريخ التحديث')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم تصدير ' . number_format($export->successful_rows) . ' مصروف بنجاح';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' و فشل تصدير ' . number_format($failedRowsCount) . ' مصروف.';
        } else {
            $body .= '.';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "period-shift-expenses-detailed-{$export->getKey()}.xlsx";
    }
}
