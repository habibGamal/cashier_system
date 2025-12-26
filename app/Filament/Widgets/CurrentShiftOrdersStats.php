<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentShiftOrdersStats extends BaseWidget
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
        return 'حالة الاوردرات';
    }

    protected function getStats(): array
    {
        $currentShift = $this->getCurrentShift();

        if (! $currentShift) {
            return [];
        }

        $orderStats = $this->shiftsReportService->calculateOrderStats($currentShift);

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
                ->description('بقيمة '.format_money($orderStats['cancelled']['value']).' - ربح '.format_money($orderStats['cancelled']['profit']))
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

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
