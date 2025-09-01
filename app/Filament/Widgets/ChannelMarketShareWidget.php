<?php

namespace App\Filament\Widgets;

use App\Services\ChannelPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ChannelMarketShareWidget extends ChartWidget
{
    protected ?string $heading = 'الحصة السوقية للقنوات';
    protected ?string $description = 'توزيع الإيرادات والطلبات حسب قنوات البيع';

    protected ?string $maxHeight = '350px';
    protected static bool $isLazy = false;

    use InteractsWithPageFilters;

    protected ChannelPerformanceReportService $channelReportService;

    public function boot(): void
    {
        $this->channelReportService = app(ChannelPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $metrics = $this->channelReportService->getChannelEfficiencyMetrics($startDate, $endDate);
        $channelData = $metrics['channel_summary'];

        $labels = $channelData->pluck('type_label')->toArray();
        $revenueData = $channelData->pluck('total_sales')->toArray();
        $ordersData = $channelData->pluck('total_orders')->toArray();

        // Define colors for different channels
        $colors = [
            '#10B981', // Green
            '#3B82F6', // Blue
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Orange
        ];

        return [
            'datasets' => [
                [
                    'label' => 'الإيرادات (ج.م)',
                    'data' => $revenueData,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                    'borderColor' => array_slice($colors, 0, count($labels)),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString() + ' ج.م (' + percentage + '%)';
                        }",
                    ],
                ],
            ],
        ];
    }
}
