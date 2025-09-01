<?php

namespace App\Filament\Pages\Reports;

use Filament\Schemas\Schema;
use App\Filament\Widgets\StockReportTable;
use App\Filament\Traits\AdminAccess;
use App\Filament\Traits\ViewerAccess;
use App\Filament\Components\PeriodFilterFormComponent;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class StockReport extends BaseDashboard
{
    use HasFiltersForm ,ViewerAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $routePath = 'orders-report';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير المخزون';

    protected static ?string $title = 'تقرير المخزون';

    protected static ?int $navigationSort = 1;


    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                PeriodFilterFormComponent::make(
                    'اختر الفترة الزمنية لتحليل المخزون',
                    'last_30_days',
                    29
                ),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            StockReportTable::class,
        ];
    }

}
