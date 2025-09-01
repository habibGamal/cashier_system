<?php

namespace App\Filament\Widgets;

use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;

class CustomersPerformanceStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getStats(): array
    {
        $summary = $this->getPeriodSummary();

        return [
            Stat::make('إجمالي العملاء', $summary['total_customers'])
                ->description('عدد العملاء الذين حققوا مبيعات')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->color('primary'),

            Stat::make('إجمالي الطلبات', number_format($summary['total_orders']))
                ->description('إجمالي عدد الطلبات المكتملة')
                ->descriptionIcon('heroicon-m-shopping-bag', IconPosition::Before)
                ->color('info'),

            Stat::make('إجمالي قيمة المبيعات', number_format($summary['total_sales'], 2) . ' ج.م')
                ->description('إجمالي قيمة المبيعات لجميع العملاء المسجلين')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),

            Stat::make('إجمالي الأرباح', number_format($summary['total_profit'], 2) . ' ج.م')
                ->description('إجمالي الأرباح المحققة من العملاء')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::Before)
                ->color('warning'),

            Stat::make('متوسط قيمة الطلب', number_format($summary['avg_order_value'], 2) . ' ج.م')
                ->description('متوسط قيمة الطلب الواحد')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('info'),

            Stat::make('متوسط الطلبات لكل عميل', number_format($summary['avg_orders_per_customer'], 1))
                ->description('متوسط عدد الطلبات لكل عميل')
                ->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before)
                ->color('gray'),

            Stat::make('أفضل عميل بالمبيعات', $summary['top_customer_by_sales']?->name ?? 'لا يوجد')
                ->description($summary['top_customer_by_sales'] ?
                    number_format($summary['top_customer_by_sales']->total_sales, 2) . ' ج.م' :
                    'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-trophy', IconPosition::Before)
                ->color('success'),

            Stat::make('أفضل عميل بالربح', $summary['top_customer_by_profit']?->name ?? 'لا يوجد')
                ->description($summary['top_customer_by_profit'] ?
                    number_format($summary['top_customer_by_profit']->total_profit, 2) . ' ج.م' :
                    'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-trophy', IconPosition::Before)
                ->color('warning'),

            Stat::make('أكثر عميل طلباً', $summary['most_frequent_customer']?->name ?? 'لا يوجد')
                ->description($summary['most_frequent_customer'] ?
                    $summary['most_frequent_customer']->total_orders . ' طلب' :
                    'لا توجد طلبات')
                ->descriptionIcon('heroicon-m-star', IconPosition::Before)
                ->color('primary'),

            Stat::make('أعلى متوسط قيمة طلب', $summary['highest_avg_order_customer']?->name ?? 'لا يوجد')
                ->description($summary['highest_avg_order_customer'] ?
                    number_format($summary['highest_avg_order_customer']->avg_order_value, 2) . ' ج.م' :
                    'لا توجد طلبات')
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::Before)
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    private function getPeriodSummary(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->customersReportService->getPeriodSummary($startDate, $endDate);
    }
}
