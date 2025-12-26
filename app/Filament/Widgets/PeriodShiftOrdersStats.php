<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeriodShiftOrdersStats extends BaseWidget
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
        return 'حالة الاوردرات';
    }

    protected function getStats(): array
    {
        $orderStats = $this->calculatePeriodOrderStats();

        return [
            Stat::make('الاوردرات المكتملة', $orderStats['completed']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderStats['completed']['value']).' - ربح '.format_money($orderStats['completed']['profit']))
                ->descriptionIcon('heroicon-m-check-circle')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{status:'completed'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('success'),

            Stat::make('الاوردرات تحت التشغيل', $orderStats['processing']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderStats['processing']['value']).' - ربح '.format_money($orderStats['processing']['profit']))
                ->descriptionIcon('heroicon-m-clock')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{status:'processing'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('warning'),

            Stat::make('الاوردرات الملغية', $orderStats['cancelled']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderStats['cancelled']['value']).' - خسارة '.format_money($orderStats['cancelled']['profit']))
                ->descriptionIcon('heroicon-m-x-circle')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{status:'cancelled'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),
        ];
    }

    private function calculatePeriodOrderStats()
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];

            return $this->shiftsReportService->calculatePeriodOrderStats(null, null, $shiftIds);
        } else {
            $startDate = $this->pageFilters['startDate'] ?? now()->subDays(7)->startOfDay()->toDateString();
            $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

            return $this->shiftsReportService->calculatePeriodOrderStats($startDate, $endDate, null);
        }
    }
}
