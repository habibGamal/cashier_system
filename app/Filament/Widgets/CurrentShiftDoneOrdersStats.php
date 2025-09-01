<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
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

        if (!$currentShift) {
            return [];
        }

        $orderTypeStats = $this->shiftsReportService->calculateOrderTypeStats($currentShift);

        return [
            Stat::make('الاوردرات الصالة', $orderTypeStats['dineIn']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['dineIn']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['dineIn']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['dineIn']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['dineIn']['value'] / $orderTypeStats['dineIn']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-home')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'dine_in'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('success'),

            Stat::make('الاوردرات ديليفري', $orderTypeStats['delivery']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['delivery']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['delivery']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['delivery']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['delivery']['value'] / $orderTypeStats['delivery']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-truck')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'delivery'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),

            Stat::make('الاوردرات تيك اواي', $orderTypeStats['takeaway']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['takeaway']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['takeaway']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['takeaway']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['takeaway']['value'] / $orderTypeStats['takeaway']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'takeaway'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('info'),

            Stat::make('الاوردرات طلبات', $orderTypeStats['talabat']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['talabat']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['talabat']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['talabat']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['talabat']['value'] / $orderTypeStats['talabat']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'talabat'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('warning'),

            Stat::make('الاوردرات اونلاين ديليفري', $orderTypeStats['webDelivery']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['webDelivery']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['webDelivery']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['webDelivery']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['webDelivery']['value'] / $orderTypeStats['webDelivery']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'web_delivery'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('danger'),

            Stat::make('الاوردرات اونلاين تيك اواي', $orderTypeStats['webTakeaway']['count'] . ' اوردر')
                ->description('بقيمة ' . number_format($orderTypeStats['webTakeaway']['value'], 2) . ' جنيه - ربح ' . number_format($orderTypeStats['webTakeaway']['profit'], 2) . ' جنيه' .
                    ($orderTypeStats['webTakeaway']['count'] > 0 ? ' - متوسط ' . number_format($orderTypeStats['webTakeaway']['value'] / $orderTypeStats['webTakeaway']['count'], 2) . ' جنيه' : ''))
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->extraAttributes([
                    'class' => 'transition hover:scale-105 cursor-pointer',
                    'wire:click' => <<<JS
                        \$dispatch('filterUpdate',{filter:{type:'web_takeaway'}} )
                        document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    JS
                ])
                ->color('info'),
        ];
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
