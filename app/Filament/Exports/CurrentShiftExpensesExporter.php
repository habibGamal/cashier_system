<?php

namespace App\Filament\Exports;

use App\Models\Shift;
use App\Models\Expense;
use App\Models\ExpenceType;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CurrentShiftExpensesExporter extends Exporter
{
    protected static ?string $model = ExpenceType::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('نوع المصروف'),

            ExportColumn::make('expense_count')
                ->label('عدد المصروفات')
                ->state(function ($record) {
                    // This will be populated by the widget's query
                    return $record->expense_count ?? 0;
                }),

            ExportColumn::make('total_amount')
                ->label('الإجمالي (جنيه)')
                ->state(function ($record) {
                    // This will be populated by the widget's query
                    return number_format($record->total_amount ?? 0, 2);
                }),

            ExportColumn::make('avg_month_rate')
                ->label('الميزانية الشهرية (جنيه)')
                ->state(function ($record) {
                    return $record->avg_month_rate ? number_format((float) $record->avg_month_rate, 2) : 'غير محدد';
                }),


            ExportColumn::make('individual_expenses')
                ->label('تفاصيل المصروفات')
                ->state(function ($record) {
                    // Get individual expenses for this type in current shift
                    $currentShift = Shift::where('closed', false)
                        ->where('end_at', null)
                        ->first();

                    if (!$currentShift) {
                        return 'لا توجد شفت مفتوحة';
                    }

                    $expenses = Expense::where('shift_id', $currentShift->id)
                        ->where('expence_type_id', $record->id)
                        ->get();

                    if ($expenses->isEmpty()) {
                        return 'لا توجد مصروفات';
                    }

                    return $expenses->map(function ($expense) {
                        $notes = $expense->notes ? " ({$expense->notes})" : '';
                        $date = $expense->created_at->format('d/m/Y H:i');
                        $amount = number_format((float) $expense->amount, 2);
                        return "{$amount} جنيه - {$date}{$notes}";
                    })->implode(' | ');
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير مصروفات الشفت الحالي وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'نوع مصروف' : 'نوع مصروف') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'نوع مصروف' : 'نوع مصروف') . '.';
        }

        return $body;
    }
}
