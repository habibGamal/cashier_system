<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use App\Filament\Widgets\NoProductsSalesInPeriodWidget;
use App\Filament\Widgets\ProductsSalesStatsWidget;
use App\Filament\Widgets\TopProductsBySalesWidget;
use App\Filament\Widgets\TopProductsByProfitWidget;
use App\Filament\Widgets\OrderTypePerformanceWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Filament\Widgets\ProductsSalesTableWidget;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Services\ProductsSalesReportService;
use App\Filament\Components\PeriodFilterFormComponent;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class ProductsSalesPerformanceReport extends BaseDashboard
{
    use HasFiltersForm, ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $routePath = 'products-sales-performance-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير أداء المنتجات';

    protected static ?string $title = 'تقرير أداء المنتجات في المبيعات';

    protected static ?int $navigationSort = 4;

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                PeriodFilterFormComponent::make(
                    'اختر الفترة الزمنية لعرض أداء المنتجات في المبيعات',
                    'last_30_days',
                    29
                ),
            ]);
    }

    public function getWidgets(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->endOfDay()->toDateString();
        $ordersCount = $this->productsReportService->getOrdersQuery(
            $startDate,
            $endDate
        )->count();

        if ($ordersCount === 0) {
            return [
                NoProductsSalesInPeriodWidget::class,
            ];
        }

        return [
            ProductsSalesStatsWidget::class,
            TopProductsBySalesWidget::class,
            TopProductsByProfitWidget::class,
            OrderTypePerformanceWidget::class,
            CategoryPerformanceWidget::class,
            ProductsSalesTableWidget::class,
        ];
    }

}
