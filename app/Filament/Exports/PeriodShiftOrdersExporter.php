<?php

namespace App\Filament\Exports;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PeriodShiftOrdersExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order_number')
                ->label('رقم الطلب'),

            ExportColumn::make('shift_info')
                ->label('الشفت')
                ->state(function ($record) {
                    return $record->shift ?
                        'شفت ' . $record->shift->start_at->format('d/m/Y H:i') :
                        'غير محدد';
                }),

            ExportColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : $state),

            ExportColumn::make('type')
                ->label('النوع')
                ->formatStateUsing(fn ($state) => $state instanceof OrderType ? $state->label() : $state),

            ExportColumn::make('customer_name')
                ->label('العميل')
                ->state(function ($record) {
                    return $record->customer?->name ?? 'غير محدد';
                }),

            ExportColumn::make('sub_total')
                ->label('المجموع الفرعي (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('tax')
                ->label('الضريبة (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('service')
                ->label('الخدمة (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('discount')
                ->label('الخصم (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('total')
                ->label('الإجمالي (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('profit')
                ->label('الربح (جنيه)')
                ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),

            ExportColumn::make('payment_methods')
                ->label('طرق الدفع')
                ->state(function ($record) {
                    $methods = $record->payments
                        ->pluck('method')
                        ->map(fn($method) => $method instanceof PaymentMethod ? $method->label() : $method)
                        ->unique()
                        ->implode(', ');
                    return $methods ?: 'غير محدد';
                }),

            ExportColumn::make('cash_amount')
                ->label('مدفوع كاش (جنيه)')
                ->state(function ($record) {
                    $amount = $record->payments
                        ->where('method', PaymentMethod::CASH)
                        ->sum('amount');
                    return number_format($amount, 2);
                }),

            ExportColumn::make('card_amount')
                ->label('مدفوع فيزا (جنيه)')
                ->state(function ($record) {
                    $amount = $record->payments
                        ->where('method', PaymentMethod::CARD)
                        ->sum('amount');
                    return number_format($amount, 2);
                }),

            ExportColumn::make('talabat_card_amount')
                ->label('مدفوع بطاقة طلبات (جنيه)')
                ->state(function ($record) {
                    $amount = $record->payments
                        ->where('method', PaymentMethod::TALABAT_CARD)
                        ->sum('amount');
                    return number_format($amount, 2);
                }),

            ExportColumn::make('employee_name')
                ->label('الموظف')
                ->state(function ($record) {
                    return $record->user?->name ?? 'غير محدد';
                }),

            ExportColumn::make('created_at')
                ->label('وقت الإنشاء')
                ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i:s') ?? ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير طلبات فترة الشفتات وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'طلب' : 'طلب') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'طلب' : 'طلب') . '.';
        }

        return $body;
    }
}
