<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Traits\ViewerAccess;
use App\Filament\Widgets\MinimumStockFinancialStats;
use App\Filament\Widgets\MinimumStockRecommendationsTable;
use App\Filament\Widgets\MinimumStockStats;
use App\Filament\Widgets\MinimumStockTable;
use App\Filament\Widgets\NoLowStockWidget;
use App\Services\MinimumStockReportService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class MinimumStockReport extends BaseDashboard
{
    use HasFiltersForm, ViewerAccess;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $routePath = 'minimum-stock-report';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير المخزون المنخفض';

    protected static ?string $title = 'تقرير المخزون المنخفض';

    protected static ?int $navigationSort = 2;

    protected MinimumStockReportService $minimumStockReportService;

    public function boot(): void
    {
        $this->minimumStockReportService = app(MinimumStockReportService::class);
    }

    public function getWidgets(): array
    {
        $productsBelowMinStockCount = $this->minimumStockReportService->getProductsBelowMinStockCount();

        if ($productsBelowMinStockCount === 0) {
            return [
                NoLowStockWidget::class,
            ];
        }

        return [
            MinimumStockStats::class,
            MinimumStockFinancialStats::class,
            MinimumStockTable::class,
        ];
    }
}
