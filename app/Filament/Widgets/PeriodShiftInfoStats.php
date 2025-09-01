<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class PeriodShiftInfoStats extends BaseWidget
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
        $periodInfo = $this->getPeriodInfo();
        return $periodInfo['title'];
    }

    protected function getStats(): array
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];
            $info = $this->shiftsReportService->getShiftsInfo(null, null, $shiftIds);
        } else {
            $startDate = Carbon::parse($this->pageFilters['startDate'])->startOfDay();
            $endDate = Carbon::parse($this->pageFilters['endDate'])->endOfDay();
            $info = $this->shiftsReportService->getShiftsInfo($startDate, $endDate, null);
        }

        $periodInfo = $this->getPeriodInfo();
        $totalShifts = $info->total_shifts;

        $totalDuration = $info->total_minutes;
        $usersCount = $info->distinct_users;

        $avgDuration = $totalShifts > 0 ? $totalDuration / $totalShifts : 0;

        return [
            Stat::make('عدد الشفتات', $totalShifts . ' شفت')
                ->description($periodInfo['description'])
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('متوسط مدة الشفت', $this->formatDuration($avgDuration))
                ->description('متوسط مدة الشفت الواحد')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning'),

            Stat::make('عدد الموظفين', $usersCount . ' موظف')
                ->description('الموظفون المسؤولون عن الشفتات')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }

    private function getPeriodInfo(): array
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];
            return $this->shiftsReportService->getPeriodInfo(null, null, $shiftIds);
        } else {
            $startDate = $this->pageFilters['startDate'] ?? now()->subDays(7)->startOfDay()->toDateString();
            $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();
            return $this->shiftsReportService->getPeriodInfo($startDate, $endDate, null);
        }
    }

    private function formatDuration(float $minutes): string
    {
        if ($minutes <= 0) {
            return '0 دقيقة';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d ساعة', $hours, $remainingMinutes);
        }

        return sprintf('%d دقيقة', $remainingMinutes);
    }
}
