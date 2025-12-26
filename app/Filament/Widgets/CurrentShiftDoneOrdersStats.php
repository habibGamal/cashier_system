<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentShiftDoneOrdersStats extends BaseWidget
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
        return 'الاوردرات المكتملة';
    }

    protected function getStats(): array
    {
        $currentShift = $this->getCurrentShift();

        if (! $currentShift) {
            return [];
        }

        $orderTypeStats = $this->shiftsReportService->calculateOrderTypeStats($currentShift);

        return [
            Stat::make('الاوردرات ديليفري', $orderTypeStats['delivery']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderTypeStats['delivery']['value']).' - ربح '.format_money($orderTypeStats['delivery']['profit']).
                    ($orderTypeStats['delivery']['count'] > 0 ? ' - متوسط '.format_money($orderTypeStats['delivery']['value'] / $orderTypeStats['delivery']['count']) : ''))
                ->descriptionIcon('heroicon-m-truck')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{type:'delivery'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),

            Stat::make('الاوردرات تيك اواي', $orderTypeStats['takeaway']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderTypeStats['takeaway']['value']).' - ربح '.format_money($orderTypeStats['takeaway']['profit']).
                    ($orderTypeStats['takeaway']['count'] > 0 ? ' - متوسط '.format_money($orderTypeStats['takeaway']['value'] / $orderTypeStats['takeaway']['count']) : ''))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{type:'takeaway'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('info'),

            Stat::make('الاوردرات اونلاين ديليفري', $orderTypeStats['webDelivery']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderTypeStats['webDelivery']['value']).' - ربح '.format_money($orderTypeStats['webDelivery']['profit']).
                    ($orderTypeStats['webDelivery']['count'] > 0 ? ' - متوسط '.format_money($orderTypeStats['webDelivery']['value'] / $orderTypeStats['webDelivery']['count']) : ''))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{type:'web_delivery'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),

            Stat::make('الاوردرات اونلاين تيك اواي', $orderTypeStats['webTakeaway']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderTypeStats['webTakeaway']['value']).' - ربح '.format_money($orderTypeStats['webTakeaway']['profit']).
                    ($orderTypeStats['webTakeaway']['count'] > 0 ? ' - متوسط '.format_money($orderTypeStats['webTakeaway']['value'] / $orderTypeStats['webTakeaway']['count']) : ''))
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{type:'web_takeaway'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('info'),

            Stat::make('البيع المباشر', $orderTypeStats['directSale']['count'].' اوردر')
                ->description('بقيمة '.format_money($orderTypeStats['directSale']['value']).' - ربح '.format_money($orderTypeStats['directSale']['profit']).
                    ($orderTypeStats['directSale']['count'] > 0 ? ' - متوسط '.format_money($orderTypeStats['directSale']['value'] / $orderTypeStats['directSale']['count']) : ''))
                ->descriptionIcon('heroicon-o-banknotes')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<'JS'
                        $dispatch('filterUpdate',{filter:{type:'direct_sale'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('success'),
        ];
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
