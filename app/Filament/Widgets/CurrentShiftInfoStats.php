<?php

namespace App\Filament\Widgets;

use App\Models\Shift;
use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class CurrentShiftInfoStats extends BaseWidget
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
        return 'معلومات الشفت الحالي';
    }

    protected function getStats(): array
    {
        $currentShift = $this->getCurrentShift();

        if (!$currentShift) {
            return [];
        }

        $startTime = Carbon::parse($currentShift->start_at);
        $now = Carbon::now();
        $duration = $startTime->diff($now);
        $durationText = $duration->format('%H:%I:%S');

        return [
            Stat::make('وقت بداية الشفت', $startTime->format('d/m/Y H:i'))
                ->description('تاريخ ووقت بداية الشفت')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('مدة الشفت', $durationText)
                ->description('المدة المنقضية منذ بداية الشفت')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning'),

            Stat::make('النقدية البدائية', number_format((float)$currentShift->start_cash, 2) . ' جنيه')
                ->description('النقدية الموجودة في بداية الشفت')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('الموظف المسؤول', $currentShift->user->name ?? 'غير محدد')
                ->description('الموظف المسؤول عن الشفت')
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),
        ];
    }

    private function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
