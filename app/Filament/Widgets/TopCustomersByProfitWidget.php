<?php

namespace App\Filament\Widgets;

use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopCustomersByProfitWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ?string $heading = 'أفضل 10 عملاء بالأرباح';

    protected int|string|array $columnSpan = 'lg:col-span-2';

    protected ?string $maxHeight = '300px';

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $topCustomers = $this->customersReportService->getCustomersPerformanceQuery($startDate, $endDate)
            ->orderByDesc('total_profit')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'الأرباح (ج.م)',
                    'data' => $topCustomers->pluck('total_profit')->toArray(),
                    'backgroundColor' => [
                        'rgba(255, 193, 7, 0.2)',
                        'rgba(40, 167, 69, 0.2)',
                        'rgba(220, 53, 69, 0.2)',
                        'rgba(23, 162, 184, 0.2)',
                        'rgba(108, 117, 125, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 205, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ],
                    'borderColor' => [
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $topCustomers->map(function ($customer) {
                return mb_strlen($customer->name, 'UTF-8') > 15
                    ? mb_substr($customer->name, 0, 15, 'UTF-8') . '...'
                    : $customer->name;
            })->toArray(),
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
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // 'ticks' => [
                    //     'callback' => new \Filament\Support\RawJs('function(value) { return value.toLocaleString() + " ج.م"; }'),
                    // ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
