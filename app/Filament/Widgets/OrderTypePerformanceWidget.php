<?php

namespace App\Filament\Widgets;

use App\Services\ProductsSalesReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrderTypePerformanceWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ?string $heading = 'أداء المبيعات حسب نوع الطلب';

    protected int | string | array $columnSpan = 2;

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    protected function getData(): array
    {
        $performance = $this->getOrderTypePerformance();
        // dd($performance);
        $labels = [];
        $salesData = [];
        $profitData = [];
        foreach ($performance as $type => $data) {
            if ($data['total_sales'] > 0) {
                $labels[] = $data['label'];
                $salesData[] = $data['total_sales'];
                $profitData[] = $data['total_profit'];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (ج.م)',
                    'data' => $salesData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.1)',
                        'rgba(16, 185, 129, 0.1)',
                        'rgba(245, 101, 101, 0.1)',
                        'rgba(251, 191, 36, 0.1)',
                        'rgba(139, 92, 246, 0.1)',
                        'rgba(236, 72, 153, 0.1)',
                        'rgba(156, 163, 175, 0.1)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 101, 101)',
                        'rgb(251, 191, 36)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                        'rgb(156, 163, 175)',
                    ],
                    'borderWidth' => 2,
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + new Intl.NumberFormat("ar-EG", {
                                style: "currency",
                                currency: "EGP"
                            }).format(context.parsed.y);
                        }'
                    ]
                ]
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("ar-EG", {
                                style: "currency",
                                currency: "EGP",
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }'
                    ]
                ],
            ],
        ];
    }

    private function getOrderTypePerformance(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->productsReportService->getOrderTypePerformance($startDate, $endDate);
    }
}
