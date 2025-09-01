<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use App\Filament\Widgets\NoCustomersSalesInPeriodWidget;
use App\Filament\Widgets\CustomersPerformanceStatsWidget;
use App\Filament\Widgets\CustomerLoyaltyInsightsWidget;
use App\Filament\Widgets\TopCustomersBySalesWidget;
use App\Filament\Widgets\TopCustomersByProfitWidget;
use App\Filament\Widgets\CustomerSegmentsWidget;
use App\Filament\Widgets\CustomerOrderTypePerformanceWidget;
use App\Filament\Widgets\CustomerActivityTrendWidget;
use App\Filament\Widgets\CustomersPerformanceTableWidget;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Services\CustomersPerformanceReportService;
use App\Filament\Components\PeriodFilterFormComponent;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class CustomersPerformanceReport extends BaseDashboard
{
    use HasFiltersForm, ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string $routePath = 'customers-performance-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير أداء العملاء';

    protected static ?string $title = 'تقرير أداء العملاء في المبيعات';

    protected static ?int $navigationSort = 5;

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                PeriodFilterFormComponent::make(
                    'اختر الفترة الزمنية لعرض أداء العملاء في المبيعات',
                    'last_30_days',
                    29
                ),
            ]);
    }

    public function getWidgets(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->endOfDay()->toDateString();
        $ordersCount = $this->customersReportService->getOrdersQuery(
            $startDate,
            $endDate
        )->count();

        if ($ordersCount === 0) {
            return [
                NoCustomersSalesInPeriodWidget::class,
            ];
        }

        return [
            CustomersPerformanceStatsWidget::class,
            CustomerLoyaltyInsightsWidget::class,
            TopCustomersBySalesWidget::class,
            TopCustomersByProfitWidget::class,
            CustomerSegmentsWidget::class,
            CustomerOrderTypePerformanceWidget::class,
            CustomerActivityTrendWidget::class,
            CustomersPerformanceTableWidget::class,
        ];
    }
}
