<?php

namespace App\Filament\Widgets;

use App\Services\ChannelPerformanceReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;

class ChannelPerformanceStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ChannelPerformanceReportService $channelReportService;

    public function boot(): void
    {
        $this->channelReportService = app(ChannelPerformanceReportService::class);
    }

    protected function getStats(): array
    {
        $metrics = $this->getChannelMetrics();

        return [
            Stat::make('إجمالي الإيرادات', number_format($metrics['total_revenue'], 2) . ' ج.م')
                ->description('إجمالي إيرادات جميع القنوات')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),

            Stat::make('إجمالي الطلبات', number_format($metrics['total_orders']))
                ->description('إجمالي عدد الطلبات من جميع القنوات')
                ->descriptionIcon('heroicon-m-shopping-bag', IconPosition::Before)
                ->color('primary'),

            Stat::make('إجمالي العملاء', number_format($metrics['total_customers']))
                ->description('إجمالي العملاء الفريدين')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->color('info'),

            Stat::make('متوسط قيمة الطلب', number_format($metrics['avg_order_value'], 2) . ' ج.م')
                ->description('متوسط قيمة الطلب عبر جميع القنوات')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('warning'),

            Stat::make('أفضل قناة (إيرادات)',
                $metrics['top_channel_by_revenue'] ? $metrics['top_channel_by_revenue']->type_label : 'لا توجد')
                ->description($metrics['top_channel_by_revenue'] ?
                    number_format($metrics['top_channel_by_revenue']->total_sales, 2) . ' ج.م' : 'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-trophy', IconPosition::Before)
                ->color('success'),

            Stat::make('أفضل قناة (طلبات)',
                $metrics['top_channel_by_orders'] ? $metrics['top_channel_by_orders']->type_label : 'لا توجد')
                ->description($metrics['top_channel_by_orders'] ?
                    number_format($metrics['top_channel_by_orders']->total_orders) . ' طلب' : 'لا توجد طلبات')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::Before)
                ->color('primary'),

            Stat::make('أكثر القنوات كفاءة',
                $metrics['most_efficient_channel'] ? $metrics['most_efficient_channel']->type_label : 'لا توجد')
                ->description($metrics['most_efficient_channel'] ?
                    number_format($metrics['most_efficient_channel']->efficiency_score, 1) . '%' : 'لا توجد بيانات')
                ->descriptionIcon('heroicon-m-rocket-launch', IconPosition::Before)
                ->color('warning'),

            Stat::make('عدد القنوات النشطة', $metrics['channel_summary']->count())
                ->description('عدد قنوات البيع النشطة')
                ->descriptionIcon('heroicon-m-squares-plus', IconPosition::Before)
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }

    private function getChannelMetrics(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->channelReportService->getChannelEfficiencyMetrics($startDate, $endDate);
    }
}
