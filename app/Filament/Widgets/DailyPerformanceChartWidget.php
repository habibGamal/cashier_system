<?php

namespace App\Filament\Widgets;

use App\Services\PeakHoursPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DailyPerformanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'أداء المبيعات حسب أيام الأسبوع';
    protected ?string $description = 'مقارنة الأداء عبر أيام الأسبوع المختلفة';

    protected ?string $maxHeight = '350px';
    protected static bool $isLazy = false;

    use InteractsWithPageFilters;

    protected PeakHoursPerformanceReportService $peakHoursReportService;

    public function boot(): void
    {
        $this->peakHoursReportService = app(PeakHoursPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $dailyData = $this->peakHoursReportService->getDailyPerformance($startDate, $endDate);

        // Ensure we have data for all 7 days
        $dayMapping = [
            1 => 'الأحد', 2 => 'الاثنين', 3 => 'الثلاثاء', 4 => 'الأربعاء',
            5 => 'الخميس', 6 => 'الجمعة', 7 => 'السبت'
        ];

        $allDays = collect(range(1, 7))->map(function ($dayNum) use ($dailyData, $dayMapping) {
            $existing = $dailyData->firstWhere('day_of_week', $dayNum);
            return $existing ?: (object) [
                'day_of_week' => $dayNum,
                'day_label' => $dayMapping[$dayNum],
                'total_orders' => 0,
                'total_sales' => 0,
                'total_profit' => 0,
                'unique_customers' => 0,
                'avg_order_value' => 0,
            ];
        });

        $labels = $allDays->pluck('day_label')->toArray();
        $salesData = $allDays->pluck('total_sales')->toArray();
        $ordersData = $allDays->pluck('total_orders')->toArray();
        $profitData = $allDays->pluck('total_profit')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (ج.م)',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'الأرباح (ج.م)',
                    'data' => $profitData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.6)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'عدد الطلبات',
                    'data' => $ordersData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'المبيعات والأرباح (ج.م)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'عدد الطلبات',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
