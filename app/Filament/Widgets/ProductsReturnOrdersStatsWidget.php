<?php

namespace App\Filament\Widgets;

use App\Services\ProductsSalesReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductsReturnOrdersStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    public function getHeading(): string
    {
        return 'إحصائيات مرتجعات المنتجات';
    }

    protected function getStats(): array
    {
        $returnStats = $this->getReturnOrdersStats();
        $periodStats = $this->getPeriodStats();

        $returnRate = $periodStats['total_quantity'] > 0 ? ($returnStats['total_returned_quantity'] / $periodStats['total_quantity']) * 100 : 0;

        return [
            Stat::make('إجمالي المرتجعات', number_format($returnStats['total_return_orders'], 0))
                ->description('عدد طلبات المرتجعات')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('الأصناف المرتجعة', number_format($returnStats['total_returned_quantity'], 0))
                ->description('عدد القطع المرتجعة')
                ->descriptionIcon('heroicon-m-cube')
                ->color('danger'),

            Stat::make('قيمة المرتجعات', format_money($returnStats['total_refund_amount']))
                ->description('إجمالي المبالغ المردودة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('عدد المنتجات المرتجعة', number_format($returnStats['products_returned_count'], 0))
                ->description('عدد المنتجات التي تم إرجاعها')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('warning'),

            Stat::make('متوسط قيمة المرتجع', format_money($returnStats['avg_refund_amount']))
                ->description('متوسط قيمة طلب المرتجع')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('معدل الإرجاع', number_format($returnRate, 2).'%')
                ->description('نسبة المرتجعات من إجمالي المبيعات')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($returnRate > 5 ? 'danger' : ($returnRate > 2 ? 'warning' : 'success')),
        ];
    }

    private function getReturnOrdersStats()
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->productsReportService->getReturnOrdersSummary($startDate, $endDate);
    }

    private function getPeriodStats()
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->productsReportService->getPeriodSummary($startDate, $endDate);
    }
}
