<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PeriodShiftReturnOrdersStats extends BaseWidget
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
        return 'إحصائيات المرتجعات للفترة';
    }

    protected function getStats(): array
    {
        $periodStats = $this->getPeriodStats();

        $returnRate = $periodStats['returnRate'];

        return [
            Stat::make('إجمالي المرتجعات', number_format($periodStats['totalReturns'], 0))
                ->description('عدد المرتجعات المكتملة في الفترة')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('قيمة المرتجعات', number_format($periodStats['totalRefundAmount'], 2) . ' جنيه')
                ->description('إجمالي المبالغ المردودة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),


            Stat::make('معدل المرتجعات', number_format($returnRate, 2) . '%')
                ->description('نسبة المرتجعات من إجمالي الطلبات')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($returnRate > 5 ? 'danger' : ($returnRate > 2 ? 'warning' : 'success')),
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
