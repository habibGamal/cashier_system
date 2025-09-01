<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use App\Filament\Widgets\NoCustomersSalesInPeriodWidget;
use App\Filament\Widgets\PeakHoursStatsWidget;
use App\Filament\Widgets\HourlyPerformanceChartWidget;
use App\Filament\Widgets\DailyPerformanceChartWidget;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Services\PeakHoursPerformanceReportService;
use App\Filament\Components\PeriodFilterFormComponent;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class PeakHoursPerformanceReport extends BaseDashboard
{
    use HasFiltersForm, ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string $routePath = 'peak-hours-performance-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير أداء ساعات الذروة';

    protected static ?string $title = 'تقرير أداء ساعات الذروة والأنماط الزمنية';

    protected static ?int $navigationSort = 7;

    protected PeakHoursPerformanceReportService $peakHoursReportService;

    public function boot(): void
    {
        $this->peakHoursReportService = app(PeakHoursPerformanceReportService::class);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                PeriodFilterFormComponent::make(
                    'اختر الفترة الزمنية لتحليل أداء ساعات الذروة والأنماط الزمنية',
                    'last_30_days',
                    29
                ),
            ]);
    }

    public function getWidgets(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->endOfDay()->toDateString();

        $ordersCount = $this->peakHoursReportService->getOrdersQuery(
            $startDate,
            $endDate
        )->count();

        if ($ordersCount === 0) {
            return [
                NoCustomersSalesInPeriodWidget::class,
            ];
        }

        return [
            PeakHoursStatsWidget::class,
            HourlyPerformanceChartWidget::class,
            DailyPerformanceChartWidget::class,
            // \App\Filament\Widgets\PeriodPerformanceWidget::class,
            // \App\Filament\Widgets\StaffOptimizationWidget::class,
            // \App\Filament\Widgets\CustomerTrafficPatternsWidget::class,
            // \App\Filament\Widgets\OrderTypeHourlyPerformanceWidget::class,
            // \App\Filament\Widgets\HourlyPerformanceTableWidget::class,
        ];
    }
}
