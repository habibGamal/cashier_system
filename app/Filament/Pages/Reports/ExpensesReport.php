<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use App\Filament\Widgets\NoShiftsInPeriodWidget;
use App\Filament\Widgets\PeriodShiftExpensesTable;
use App\Filament\Widgets\PeriodShiftExpensesDetailsTable;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Models\Shift;
use App\Services\ShiftsReportService;
use App\Filament\Components\PeriodWithShiftFilterFormComponent;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class ExpensesReport extends BaseDashboard
{
    use HasFiltersForm,ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $routePath = 'expenses-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير المصروفات';

    protected static ?string $title = 'تقرير المصروفات';

    protected static ?int $navigationSort = 4;

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components(
                PeriodWithShiftFilterFormComponent::make(
                    'اختر الفترة الزمنية لعرض تقارير المصروفات',
                    'اختر الشفتات المحددة',
                    'last_7_days',
                    6
                )
            );
    }

    public function getWidgets(): array
    {
        $filterType = $this->filters['filterType'] ?? 'period';
        $shiftsCount = 0;

        if ($filterType === 'shifts') {
            $shiftIds = $this->filters['shifts'] ?? [];
            $shiftsCount = $this->shiftsReportService->getShiftsCountInPeriod(null, null, $shiftIds);
        } else {
            $startDate = $this->filters['startDate'] ?? now()->subDays(6)->startOfDay()->toDateString();
            $endDate = $this->filters['endDate'] ?? now()->endOfDay()->toDateString();
            $shiftsCount = $this->shiftsReportService->getShiftsCountInPeriod($startDate, $endDate, null);
        }

        if ($shiftsCount === 0) {
            return [
                NoShiftsInPeriodWidget::class,
            ];
        }

        return [
            PeriodShiftExpensesTable::class,
            PeriodShiftExpensesDetailsTable::class,
        ];
    }
}
