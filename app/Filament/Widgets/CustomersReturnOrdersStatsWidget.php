<?php

namespace App\Filament\Widgets;

use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomersReturnOrdersStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    public function getHeading(): string
    {
        return 'إحصائيات مرتجعات العملاء';
    }

    protected function getStats(): array
    {
        $returnStats = $this->getReturnOrdersStats();

        return [
            Stat::make('إجمالي المرتجعات', number_format($returnStats['total_return_orders'], 0))
                ->description('عدد طلبات المرتجعات من العملاء')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('العملاء المرتجعون', number_format($returnStats['customers_with_returns'], 0))
                ->description('عدد العملاء الذين أرجعوا طلبات')
                ->descriptionIcon('heroicon-m-users')
                ->color('danger'),

            Stat::make('المبلغ المسترد', format_money($returnStats['total_refund_amount']))
                ->description('إجمالي المبالغ المردودة للعملاء')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('متوسط قيمة المرتجع', format_money($returnStats['avg_refund_amount']))
                ->description('متوسط قيمة المرتجع للعميل الواحد')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('الأصناف المرتجعة', number_format($returnStats['total_returned_quantity'], 0))
                ->description('عدد القطع المرتجعة من العملاء')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),

            Stat::make('أكثر العملاء إرجاعاً', $returnStats['top_returning_customer']->name ?? 'لا يوجد')
                ->description($returnStats['top_returning_customer'] ?
                    number_format($returnStats['top_returning_customer']->return_orders_count).' مرتجع' :
                    'لم يتم العثور على مرتجعات')
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),
        ];
    }

    private function getReturnOrdersStats()
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->customersReportService->getCustomersReturnOrdersSummary($startDate, $endDate);
    }
}
