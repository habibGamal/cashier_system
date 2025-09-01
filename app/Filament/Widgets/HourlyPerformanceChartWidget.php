<?php

namespace App\Filament\Widgets;

use App\Services\PeakHoursPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class HourlyPerformanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'أداء المبيعات حسب الساعة';
    protected ?string $description = 'تحليل المبيعات والطلبات على مدار 24 ساعة';

    protected ?string $maxHeight = '400px';
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

        $hourlyData = $this->peakHoursReportService->getHourlyPerformance($startDate, $endDate);

        // Ensure we have data for all 24 hours
        $allHours = collect(range(0, 23))->map(function ($hour) use ($hourlyData) {
            $existing = $hourlyData->firstWhere('hour', $hour);
            return $existing ?: (object) [
                'hour' => $hour,
                'hour_label' => sprintf('%02d:00', $hour),
                'total_orders' => 0,
                'total_sales' => 0,
                'total_profit' => 0,
                'unique_customers' => 0,
            ];
        });

        $labels = $allHours->pluck('hour_label')->toArray();
        $salesData = $allHours->pluck('total_sales')->toArray();
        $ordersData = $allHours->pluck('total_orders')->toArray();
        $customersData = $allHours->pluck('unique_customers')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (ج.م)',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                    'type' => 'line',
                ],
                [
                    'label' => 'عدد الطلبات',
                    'data' => $ordersData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'عدد العملاء',
                    'data' => $customersData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                    'type' => 'line',
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
                        'text' => 'المبيعات (ج.م)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'عدد الطلبات/العملاء',
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
