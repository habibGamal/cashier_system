<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PeriodShiftMoneyInfoStats extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getHeading(): string
    {
        return 'الإيراد الإجمالي';
    }

    protected function getStats(): array
    {
        $stats = $this->getPeriodStats();
        $hasReturns = ($stats['totalRefundAmount'] ?? 0) > 0;
        $netSales = $stats['sales'] - ($stats['totalRefundAmount'] ?? 0);
        $netProfit = $stats['profit'] - $stats['expenses'];
        $netRevenue = $netSales - $stats['expenses'];

        $result = [
            Stat::make('المبيعات', number_format($stats['sales'], 2) . ' جنيه')
                ->description('إجمالي المبيعات المكتملة')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('success'),
        ];

        // Add net sales if there are returns
        if ($hasReturns) {
            $result[] = Stat::make('صافي المبيعات', number_format($netSales, 2) . ' جنيه')
                ->description('بعد خصم المرتجعات (' . number_format($stats['totalRefundAmount'], 2) . ' جنيه)')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('info');
        }

        $result[] = Stat::make('الارباح', number_format($stats['profit'], 2) . ' جنيه')
            ->description(number_format($stats['profitPercent'], 2) . '% من المبيعات')
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('success');

        $result[] = Stat::make('المصروفات', number_format($stats['expenses'], 2) . ' جنيه')
            ->description('إجمالي مصروفات الفترة')
            ->descriptionIcon('heroicon-m-arrow-down-circle')
            ->extraAttributes([
                'class' => 'transition hover:scale-105 cursor-pointer',
                'wire:click' => <<<JS
                    \$dispatch()
                    document.getElementById('expenses_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                JS
            ])
            ->color('danger');

        // Add net revenue if there are returns
        if ($hasReturns) {
            $result[] = Stat::make('صافي الإيراد', number_format($netRevenue, 2) . ' جنيه')
                ->description('صافي المبيعات بعد خصم المصروفات')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($netRevenue >= 0 ? 'success' : 'danger');
        }

        $result[] = Stat::make('الخصومات', number_format($stats['discounts'], 2) . ' جنيه')
            ->description('إجمالي الخصومات المطبقة')
            ->descriptionIcon('heroicon-m-percent-badge')
            ->extraAttributes([
                'class' => 'transition hover:scale-105 cursor-pointer',
                'wire:click' => <<<JS
                    \$dispatch('filterUpdate',{filter:{has_discount:'1'}} )
                    document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                JS
            ])
            ->color('warning');

        $result[] = Stat::make('النقود المدفوعة كاش', number_format($stats['cashPayments'], 2) . ' جنيه')
            ->description('المدفوعات النقدية')
            ->descriptionIcon('heroicon-m-banknotes')
            ->extraAttributes([
                'class' => 'transition hover:scale-105 cursor-pointer',
                'wire:click' => <<<JS
                    \$dispatch('filterUpdate',{filter:{payment_method:'cash'}} )
                    document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                JS
            ])
            ->color('primary');

        $result[] = Stat::make('النقود المدفوعة فيزا', number_format($stats['cardPayments'], 2) . ' جنيه')
            ->description('المدفوعات بالكارت')
            ->descriptionIcon('heroicon-m-credit-card')
            ->extraAttributes([
                'class' => 'transition hover:scale-105 cursor-pointer',
                'wire:click' => <<<JS
                    \$dispatch('filterUpdate',{filter:{payment_method:'card'}} )
                    document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                JS
            ])
            ->color('info');

        $result[] = Stat::make('النقود المدفوعة فيزا طلبات', number_format($stats['talabatCardPayments'], 2) . ' جنيه')
            ->description('المدفوعات بكارت طلبات')
            ->descriptionIcon('heroicon-m-device-phone-mobile')
            ->extraAttributes([
                'class' => 'transition hover:scale-105 cursor-pointer',
                'wire:click' => <<<JS
                    \$dispatch('filterUpdate',{filter:{payment_method:'talabat_card'}} )
                    document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                JS
            ])
            ->color('warning');

        $result[] = Stat::make('متوسط قيمة الاوردر', number_format($stats['avgReceiptValue'], 2) . ' جنيه')
            ->description('متوسط قيمة الطلب الواحد')
            ->descriptionIcon('heroicon-m-calculator')
            ->color('primary');

        $result[] = Stat::make('صافي الربح', number_format($netProfit, 2) . ' جنيه')
            ->description('الربح بعد خصم المصروفات')
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color($netProfit >= 0 ? 'success' : 'danger');

        return $result;
    }

    private function getPeriodStats()
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];
            return $this->shiftsReportService->calculatePeriodStats(null, null, $shiftIds);
        } else {
            $startDate = $this->pageFilters['startDate'] ?? now()->subDays(7)->startOfDay()->toDateString();
            $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();
            return $this->shiftsReportService->calculatePeriodStats($startDate, $endDate, null);
        }
    }
}
