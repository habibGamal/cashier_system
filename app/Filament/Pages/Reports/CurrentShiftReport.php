<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\NoActiveShiftWidget;
use App\Filament\Widgets\CurrentShiftInfoStats;
use App\Filament\Widgets\CurrentShiftMoneyInfoStats;
use App\Filament\Widgets\CurrentShiftOrdersStats;
use App\Filament\Widgets\CurrentShiftDoneOrdersStats;
use App\Filament\Widgets\CurrentShiftOrdersTable;
use App\Filament\Widgets\CurrentShiftExpensesDetailsTable;
use App\Filament\Widgets\CurrentShiftExpensesTable;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Services\ShiftsReportService;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Shift;

class CurrentShiftReport extends BaseDashboard
{
    use HasFiltersForm ,ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $routePath = 'current-shift-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير اليوم';

    protected static ?string $title = 'تقرير اليوم';

    protected static ?int $navigationSort = 1;

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getWidgets(): array
    {
        $currentShift = $this->getCurrentShift();

        if (!$currentShift) {
            return [
                NoActiveShiftWidget::class,
            ];
        }

        return [
            CurrentShiftInfoStats::class,
            CurrentShiftMoneyInfoStats::class,
            CurrentShiftOrdersStats::class,
            CurrentShiftDoneOrdersStats::class,
            CurrentShiftOrdersTable::class,
            CurrentShiftExpensesDetailsTable::class,
            CurrentShiftExpensesTable::class,
        ];
    }

    public function getCurrentShift(): ?Shift
    {
        return $this->shiftsReportService->getCurrentShift();
    }
}
