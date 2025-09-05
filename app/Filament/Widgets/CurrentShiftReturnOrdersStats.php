<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrentShiftReturnOrdersStats extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = '10s';

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getHeading(): string
    {
        return 'إحصائيات المرتجعات - الشفت الحالي';
    }

    protected function getStats(): array
    {
        $currentShift = $this->getCurrentShift();

        if (!$currentShift) {
            return [];
        }

        $returnStats = $this->shiftsReportService->calculateReturnOrdersStats($currentShift);

        return [
            Stat::make('إجمالي المرتجعات', $returnStats['totalReturns'])
                ->description('عدد المرتجعات المكتملة')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('قيمة المرتجعات', number_format((float)$returnStats['totalRefundAmount'], 2) . ' جنيه')
                ->description('إجمالي قيمة المبالغ المردودة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('الأصناف المرتجعة', number_format((float)$returnStats['totalItemsReturned'], 0))
                ->description('عدد القطع المرتجعة')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),

            Stat::make('معدل المرتجعات', number_format((float)$returnStats['returnRate'], 1) . '%')
                ->description('نسبة المرتجعات من إجمالي الطلبات')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($returnStats['returnRate'] > 5 ? 'danger' : ($returnStats['returnRate'] > 2 ? 'warning' : 'success')),
        ];
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
