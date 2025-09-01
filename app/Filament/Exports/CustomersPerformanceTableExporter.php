<?php

namespace App\Filament\Exports;

use Carbon\Carbon;
use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomersPerformanceTableExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('اسم العميل'),

            ExportColumn::make('phone')
                ->label('رقم الهاتف'),

            ExportColumn::make('region')
                ->label('المنطقة')
                ->state(function ($record) {
                    return $record->region ?: 'غير محدد';
                }),

            ExportColumn::make('total_orders')
                ->label('عدد الطلبات')
                ->state(function ($record) {
                    return $record->total_orders ?? 0;
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

            ExportColumn::make('avg_order_value')
                ->label('متوسط قيمة الطلب (ج.م)')
                ->state(function ($record) {
                    return number_format($record->avg_order_value ?? 0, 2);
                }),

            ExportColumn::make('profit_margin')
                ->label('هامش الربح %')
                ->state(function ($record) {
                    $totalSales = $record->total_sales ?? 0;
                    $totalProfit = $record->total_profit ?? 0;
                    return $totalSales > 0 ? number_format(($totalProfit / $totalSales) * 100, 1) . '%' : '0%';
                }),

            ExportColumn::make('customer_segment')
                ->label('تصنيف العميل')
                ->state(function ($record) {
                    if ($record->total_sales >= 5000 && $record->total_orders >= 20) {
                        return 'VIP';
                    } elseif ($record->total_sales >= 2000 && $record->total_orders >= 10) {
                        return 'مخلص';
                    } elseif ($record->total_orders >= 5) {
                        return 'عادي';
                    } else {
                        return 'جديد';
                    }
                }),

            ExportColumn::make('last_order_date')
                ->label('آخر طلب')
                ->state(function ($record) {
                    return $record->last_order_date ?
                        Carbon::parse($record->last_order_date)->format('Y-m-d') :
                        'لا يوجد';
                }),

            ExportColumn::make('first_order_date')
                ->label('أول طلب')
                ->state(function ($record) {
                    return $record->first_order_date ?
                        Carbon::parse($record->first_order_date)->format('Y-m-d') :
                        'لا يوجد';
                }),

            // Order Type Performance
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

            // Customer loyalty metrics
            ExportColumn::make('customer_lifetime_days')
                ->label('مدة العلاقة (أيام)')
                ->state(function ($record) {
                    if ($record->first_order_date && $record->last_order_date) {
                        $first = Carbon::parse($record->first_order_date);
                        $last = Carbon::parse($record->last_order_date);
                        return $first->diffInDays($last);
                    }
                    return 0;
                }),

            ExportColumn::make('orders_frequency')
                ->label('معدل الطلب (طلب/شهر)')
                ->state(function ($record) {
                    if ($record->first_order_date && $record->last_order_date && $record->total_orders > 1) {
                        $first = Carbon::parse($record->first_order_date);
                        $last = Carbon::parse($record->last_order_date);
                        $months = $first->diffInMonths($last) ?: 1;
                        return number_format($record->total_orders / $months, 2);
                    }
                    return $record->total_orders ?? 0;
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'تم إكمال تصدير تقرير أداء العملاء وتم تصدير ' . number_format($export->successful_rows) . ' ' . ($export->successful_rows == 1 ? 'عميل' : 'عميل') . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل في تصدير ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount == 1 ? 'عميل' : 'عميل') . '.';
        }

        return $body;
    }
}
