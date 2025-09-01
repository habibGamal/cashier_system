<?php

namespace App\Filament\Widgets;

use App\Services\ProductsSalesReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;

class ProductsSalesStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    protected function getStats(): array
    {
        $summary = $this->getPeriodSummary();

        return [
            Stat::make('إجمالي المنتجات المباعة', $summary['total_products'])
                ->description('عدد المنتجات التي حققت مبيعات')
                ->descriptionIcon('heroicon-m-cube', IconPosition::Before)
                ->color('primary'),

            Stat::make('إجمالي قيمة المبيعات', number_format($summary['total_sales'], 2) . ' ج.م')
                ->description('إجمالي قيمة المبيعات لجميع المنتجات')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),

            Stat::make('إجمالي الأرباح', number_format($summary['total_profit'], 2) . ' ج.م')
                ->description('إجمالي الأرباح المحققة من المبيعات')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::Before)
                ->color('warning'),

            Stat::make('متوسط هامش الربح', number_format($summary['avg_profit_margin'], 1) . '%')
                ->description('متوسط هامش الربح لجميع المنتجات')
                ->descriptionIcon('heroicon-m-calculator', IconPosition::Before)
                ->color('info'),

            Stat::make('إجمالي الكميات المباعة', number_format($summary['total_quantity']))
                ->description('إجمالي الكميات المباعة لجميع المنتجات')
                ->descriptionIcon('heroicon-m-shopping-cart', IconPosition::Before)
                ->color('gray'),

            Stat::make('أفضل منتج بالمبيعات', $summary['best_selling_product']?->name ?? 'لا يوجد')
                ->description($summary['best_selling_product'] ?
                    number_format($summary['best_selling_product']->total_sales, 2) . ' ج.م' :
                    'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-trophy', IconPosition::Before)
                ->color('success'),

            Stat::make('أفضل منتج بالربح', $summary['most_profitable_product']?->name ?? 'لا يوجد')
                ->description($summary['most_profitable_product'] ?
                    number_format($summary['most_profitable_product']->total_profit, 2) . '
                    ج.م' :
                    'لا توجد مبيعات')
                ->descriptionIcon('heroicon-m-trophy', IconPosition::Before)
                ->color('success'),
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

        return $this->productsReportService->getPeriodSummary($startDate, $endDate);
    }
}
