<?php

namespace App\Filament\Widgets;

use Filament\Actions\ExportAction;
use App\Services\ProductsSalesReportService;
use App\Filament\Exports\CategoryPerformanceExporter;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class CategoryPerformanceWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    protected static ?string $heading = 'أداء التصنيفات';

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    public function table(Table $table): Table
    {

        return $table
            ->query(
                $this->getCategoryPerformance()
            )
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير تقرير أداء التصنيفات')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(CategoryPerformanceExporter::class)
                    ->fileName(fn () => 'category-performance-' . now()->format('Y-m-d-H-i-s') . '.xlsx'),
            ])
            ->columns([
                TextColumn::make('category_name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('products_count')
                    ->label('عدد المنتجات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_quantity')
                    ->label('إجمالي الكمية')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_sales')
                    ->label('إجمالي المبيعات')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('total_profit')
                    ->label('إجمالي الربح')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('profit_margin')
                    ->label('هامش الربح %')
                    ->state(function ($record) {
                        return $record->total_sales > 0
                            ? number_format(($record->total_profit / $record->total_sales) * 100, 1) . '%'
                            : '0%';
                    }),

                TextColumn::make('avg_sales_per_product')
                    ->label('متوسط المبيعات')
                    ->getStateUsing(function ($record) {
                        $avg = $record->total_quantity > 0 ? $record->total_sales / $record->total_quantity : 0;
                        return number_format($avg, 2) . ' ج.م';
                    }),

                TextColumn::make('avg_profit_per_product')
                    ->label('متوسط الربح')
                    ->getStateUsing(function ($record) {
                        $avg = $record->total_quantity > 0 ? $record->total_profit / $record->total_quantity : 0;
                        return number_format($avg, 2) . ' ج.م';
                    }),
            ])
            ->defaultSort('total_sales', 'desc')
            ->paginated([5, 10, 25])
            ->striped();
    }

    private function getCategoryPerformance()
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->productsReportService->getCategoryPerformance($startDate, $endDate);
    }
}
