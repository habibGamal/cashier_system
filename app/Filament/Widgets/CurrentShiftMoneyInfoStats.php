<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentShiftMoneyInfoStats extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getHeading(): string
    {
        return 'الإيراد';
    }

    protected function getStats(): array
    {
        $currentShift = $this->getCurrentShift();

        if (! $currentShift) {
            return [];
        }

        $stats = $this->shiftsReportService->calculateShiftStats($currentShift);

        $returnStats = $this->shiftsReportService->calculateReturnOrdersStats($currentShift);

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
                ->description('إجمالي مصروفات الشفت')
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

            Stat::make('متوسط قيمة الاوردر', format_money($stats['avgReceiptValue']))
                ->description('متوسط قيمة الطلب الواحد')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),

            $this->getAvailableCashStat($currentShift, $stats, $returnStats),
        ];
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }

    private function getAvailableCashStat(Shift $shift, array $stats, array $returnStats): Stat
    {
        $availableCash = (float) $shift->start_cash + $stats['cashPayments'] - $stats['expenses'] - $returnStats['totalRefundAmount'];

        return Stat::make('النقدية المتاحة', format_money($availableCash))
            ->description('النقدية المتاحة في الدرج (بداية الشيفت + المدفوعات النقدية - المصروفات - إجمالي المرتجعات = النقدية المتاحة) '.currency_name())
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color($availableCash >= 0 ? 'success' : 'danger');
    }
}
