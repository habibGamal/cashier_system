<?php

namespace App\Filament\Widgets;

use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CustomerOrderTypePerformanceWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ?string $heading = 'أداء العملاء حسب نوع الطلب';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '400px';

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $orderTypePerformance = $this->customersReportService->getOrderTypePerformance($startDate, $endDate);
        $labels = [];
        $uniqueCustomers = [];
        $totalOrders = [];
        $totalSales = [];
        $totalProfit = [];

        foreach ($orderTypePerformance as $type => $data) {
            $labels[] = $data['label'];
            $uniqueCustomers[] = $data['unique_customers'];
            $totalOrders[] = $data['total_orders'];
            $totalSales[] = $data['total_sales'];
            $totalProfit[] = $data['total_profit'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد العملاء الفريدين',
                    'data' => $uniqueCustomers,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'إجمالي الطلبات',
                    'data' => $totalOrders,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'إجمالي المبيعات (ج.م)',
                    'data' => $totalSales,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'type' => 'line',
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'إجمالي الأرباح (ج.م)',
                    'data' => $totalProfit,
                    'backgroundColor' => 'rgba(255, 193, 7, 0.6)',
                    'borderColor' => 'rgba(255, 193, 7, 1)',
                    'borderWidth' => 2,
                    'type' => 'line',
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'عدد العملاء والطلبات',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'المبيعات والأرباح (ج.م)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
