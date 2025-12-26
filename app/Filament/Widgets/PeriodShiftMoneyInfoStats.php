<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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

        return [
            Stat::make('المبيعات', format_money($stats['sales']))
                ->description('إجمالي المبيعات المكتملة')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('success'),

            Stat::make('الارباح', format_money($stats['profit']))
                ->description(number_format($stats['profitPercent'], 2).'% من المبيعات')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('المصروفات', format_money($stats['expenses']))
                ->description('إجمالي مصروفات الفترة')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch()
                        document.getElementById('expenses_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),

            Stat::make('الخصومات', format_money($stats['discounts']))
                ->description('إجمالي الخصومات المطبقة')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{has_discount:'1'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('warning'),

            Stat::make('النقود المدفوعة كاش', format_money($stats['cashPayments']))
                ->description('المدفوعات النقدية')
                ->descriptionIcon('heroicon-m-banknotes')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{payment_method:'cash'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('primary'),

            Stat::make('النقود المدفوعة فيزا', format_money($stats['cardPayments']))
                ->description('المدفوعات بالكارت')
                ->descriptionIcon('heroicon-m-credit-card')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{payment_method:'card'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('info'),

            Stat::make('النقود المدفوعة فيزا طلبات', format_money($stats['talabatCardPayments']))
                ->description('المدفوعات بكارت طلبات')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{payment_method:'talabat_card'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('warning'),

            Stat::make('متوسط قيمة الاوردر', format_money($stats['avgReceiptValue']))
                ->description('متوسط قيمة الطلب الواحد')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),

            Stat::make('صافي الربح', format_money($stats['profit'] - $stats['expenses']))
                ->description('الربح بعد خصم المصروفات')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($stats['profit'] - $stats['expenses'] >= 0 ? 'success' : 'danger'),
        ];
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
