<?php

namespace App\Filament\Widgets;

use App\Services\ProductsSalesReportService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopReturnedProductsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ProductsSalesReportService $productsReportService;

    public function boot(): void
    {
        $this->productsReportService = app(ProductsSalesReportService::class);
    }

    public function getHeading(): string
    {
        return 'أكثر المنتجات إرجاعاً';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_name')
                    ->label('التصنيف')
                    ->sortable(),

                Tables\Columns\TextColumn::make('return_orders_count')
                    ->label('عدد المرتجعات')
                    ->numeric()
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_returned_quantity')
                    ->label('الكمية المرتجعة')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_returned_value')
                    ->label('قيمة المرتجعات')
                    ->money(currency_code())
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_refund_amount')
                    ->label('المبلغ المسترد')
                    ->money(currency_code())
                    ->sortable()
                    ->color('warning'),
            ])
            ->defaultSort('total_returned_quantity', 'desc')
            ->paginated([10, 25, 50])
            ->poll('10s');
    }

    protected function getTableQuery(): Builder
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(29)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $this->productsReportService->getProductsReturnOrdersPerformanceQuery($startDate, $endDate);
    }
}
