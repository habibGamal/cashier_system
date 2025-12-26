<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeriodShiftDoneOrdersStats extends BaseWidget
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
        return 'الاوردرات المكتملة حسب النوع';
    }

    protected function getStats(): array
    {
        $orderTypeStats = $this->calculatePeriodOrderTypeStats();

        $stats = [];

        // Map the new service format to the old format expected by widgets
        $statsMapping = [
            'delivery' => ['key' => 'delivery', 'label' => 'الاوردرات ديليفري', 'icon' => 'heroicon-m-truck', 'color' => 'info'],
            'takeaway' => ['key' => 'takeaway', 'label' => 'الاوردرات تيك اواي', 'icon' => 'heroicon-m-shopping-bag', 'color' => 'warning'],
            'web_delivery' => ['key' => 'webDelivery', 'label' => 'الاوردرات اونلاين ديليفري', 'icon' => 'heroicon-m-globe-alt', 'color' => 'danger'],
            'web_takeaway' => ['key' => 'webTakeaway', 'label' => 'الاوردرات اونلاين تيك اواي', 'icon' => 'heroicon-m-computer-desktop', 'color' => 'info'],
            'direct_sale' => ['key' => 'directSale', 'label' => 'البيع المباشر', 'icon' => 'heroicon-o-banknotes', 'color' => 'success'],
        ];

        foreach ($statsMapping as $enumValue => $config) {
            if (isset($orderTypeStats[$enumValue]) && $orderTypeStats[$enumValue]['count'] > 0) {
                $data = $orderTypeStats[$enumValue];
                $average = $data['count'] > 0 ? $data['value'] / $data['count'] : 0;
                $stats[] = Stat::make($config['label'], $data['count'].' اوردر')
                    ->description('بقيمة '.format_money($data['value']).' - ربح '.format_money($data['profit']).' - متوسط '.format_money($average))
                    ->descriptionIcon($config['icon'])
                    ->extraAttributes([
                        'class' => 'transition hover:scale-105 cursor-pointer',
                        'wire:click' => <<<JS
                            \$dispatch('filterUpdate',{filter:{type:'{$enumValue}'}} )
                            document.getElementById('orders_table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        JS
                    ])
                    ->color($config['color']);
            }
        }

        return $stats;
    }

    private function calculatePeriodOrderTypeStats()
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];

            return $this->shiftsReportService->calculatePeriodOrderTypeStats(null, null, $shiftIds);
        } else {
            $startDate = $this->pageFilters['startDate'] ?? now()->subDays(value: 7)->startOfDay()->toDateString();
            $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

            return $this->shiftsReportService->calculatePeriodOrderTypeStats($startDate, $endDate, null);
        }
    }
}
