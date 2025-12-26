<?php

namespace App\Filament\Widgets;

use App\Services\PeakHoursPerformanceReportService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeakHoursStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected PeakHoursPerformanceReportService $peakHoursReportService;

    public function boot(): void
    {
        $this->peakHoursReportService = app(PeakHoursPerformanceReportService::class);
    }

    protected function getStats(): array
    {
        $analysis = $this->getPeakAnalysis();

        return [
            Stat::make('إجمالي المبيعات', format_money($analysis['total_sales']))
                ->description('إجمالي المبيعات في الفترة المحددة')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),

            Stat::make('إجمالي الطلبات', number_format($analysis['total_orders']))
                ->description('إجمالي عدد الطلبات المكتملة')
                ->descriptionIcon('heroicon-m-shopping-bag', IconPosition::Before)
                ->color('primary'),

            Stat::make('ساعة الذروة (مبيعات)',
                $analysis['peak_sales_hour'] ? $analysis['peak_sales_hour']->hour_label : 'لا توجد')
                ->description($analysis['peak_sales_hour'] ?
                    format_money($analysis['peak_sales_hour']->total_sales) : 'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::Before)
                ->color('warning'),

            Stat::make('ساعة الذروة (طلبات)',
                $analysis['peak_orders_hour'] ? $analysis['peak_orders_hour']->hour_label : 'لا توجد')
                ->description($analysis['peak_orders_hour'] ?
                    $analysis['peak_orders_hour']->total_orders.' طلب' : 'لا توجد طلبات')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color('info'),

            Stat::make('أفضل يوم (مبيعات)',
                $analysis['peak_sales_day'] ? $analysis['peak_sales_day']->day_label : 'لا يوجد')
                ->description($analysis['peak_sales_day'] ?
                    format_money($analysis['peak_sales_day']->total_sales) : 'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-calendar-days', IconPosition::Before)
                ->color('success'),

            Stat::make('أفضل يوم (طلبات)',
                $analysis['peak_orders_day'] ? $analysis['peak_orders_day']->day_label : 'لا يوجد')
                ->description($analysis['peak_orders_day'] ?
                    $analysis['peak_orders_day']->total_orders.' طلب' : 'لا توجد طلبات')
                ->descriptionIcon('heroicon-m-calendar', IconPosition::Before)
                ->color('primary'),

            Stat::make('أفضل فترة', $analysis['best_period'] ?? 'غير محدد')
                ->description($analysis['best_period'] ?
                    format_money($analysis['period_performance'][$analysis['best_period']]) : 'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-sun', IconPosition::Before)
                ->color('warning'),

            Stat::make('متوسط المبيعات/ساعة', format_money($analysis['average_hourly_sales']))
                ->description('متوسط المبيعات في الساعة الواحدة')
                ->descriptionIcon('heroicon-m-chart-bar-square', IconPosition::Before)
                ->color('info'),

            Stat::make('متوسط الطلبات/ساعة', number_format($analysis['average_hourly_orders'], 1))
                ->description('متوسط عدد الطلبات في الساعة الواحدة')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    private function getPeakAnalysis(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->peakHoursReportService->getPeakAnalysis($startDate, $endDate);
    }
}
