<?php

namespace App\Filament\Widgets;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MinimumStockTable extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'المنتجات تحت الحد الأدنى';

    public function table(Table $table): Table
    {
        $query = Product::query()
            ->with(['category', 'inventoryItem'])
            ->whereHas('inventoryItem', function ($query) {
                $query->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)');
            });

        return $table
            ->query($query)
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير التقرير')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->fileName(fn() => 'minimum-stock-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx')
                // ->modifyQueryUsing(function (Builder $query) {
                //     return $query->with(['category', 'inventoryItem'])
                //         ->whereHas('inventoryItem', function ($query) {
                //             $query->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = inventory_items.product_id)');
                //         });
                // })
                ,
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('الرقم المنتج')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary'),


                TextColumn::make('category.name')
                    ->label('الفئة')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('inventoryItem.quantity')
                    ->label('المخزون الحالي')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn($record) => ' ' . $record->unit)
                    ->alignCenter()
                    ->color(fn($record) => match (true) {
                        ($record->inventoryItem->quantity ?? 0) <= 0 => 'danger',
                        ($record->inventoryItem->quantity ?? 0) < ($record->min_stock / 2) => 'danger',
                        ($record->inventoryItem->quantity ?? 0) < $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->weight('bold'),

                TextColumn::make('min_stock')
                    ->label('الحد الأدنى')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn($record) => ' ' . $record->unit)
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('shortage')
                    ->label('النقص')
                    ->state(function ($record) {
                        $currentStock = $record->inventoryItem->quantity ?? 0;
                        $shortage = max(0, $record->min_stock - $currentStock);

                        return number_format($shortage, 2);
                    })
                    ->suffix(fn($record) => ' ' . $record->unit)
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold'),

                TextColumn::make('cost')
                    ->label('سعر التكلفة')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' جنيه')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('shortage_cost')
                    ->label('تكلفة النقص')
                    ->state(function ($record) {
                        $currentStock = $record->inventoryItem->quantity ?? 0;
                        $shortage = max(0, $record->min_stock - $currentStock);
                        $cost = $shortage * $record->cost;

                        return number_format($cost, 2);
                    })
                    ->suffix(' جنيه')
                    ->alignCenter()
                    ->color('warning'),

                TextColumn::make('avg_purchase_quantity')
                    ->label('كمية الشراء المعتادة')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn($record) => ' ' . $record->unit)
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('recommended_cost')
                    ->label('التكلفة المقترحة')
                    ->state(function ($record) {
                        $currentStock = $record->inventoryItem->quantity ?? 0;
                        $shortage = max(0, $record->min_stock - $currentStock);
                        $recommendedQty = max($shortage, $record->avg_purchase_quantity ?? 1);
                        $cost = $recommendedQty * $record->cost;

                        return number_format($cost, 2);
                    })
                    ->suffix(' جنيه')
                    ->alignCenter()
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('unit')
                    ->label('الوحدة')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('الفئة')
                    ->relationship('category', 'name'),

                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(ProductType::class),

                TernaryFilter::make('zero_stock')
                    ->label('مخزون صفر')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('inventoryItem', function ($subQuery) {
                            $subQuery->where('quantity', '<=', 0);
                        }),
                        false: fn(Builder $query) => $query->whereHas('inventoryItem', function ($subQuery) {
                            $subQuery->where('quantity', '>', 0);
                        }),
                        blank: fn(Builder $query) => $query,
                    ),

                TernaryFilter::make('critical_stock')
                    ->label('مخزون حرج')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('inventoryItem', function ($subQuery) {
                            $subQuery->whereRaw('quantity < (SELECT min_stock / 2 FROM products WHERE products.id = inventory_items.product_id)');
                        }),
                        false: fn(Builder $query) => $query->whereHas('inventoryItem', function ($subQuery) {
                            $subQuery->whereRaw('quantity >= (SELECT min_stock / 2 FROM products WHERE products.id = inventory_items.product_id)');
                        }),
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->emptyStateHeading('لا توجد منتجات تحت الحد الأدنى')
            ->emptyStateDescription('جميع المنتجات لديها مخزون كافي.')
            ->emptyStateIcon('heroicon-o-check-circle')
            // ->recordUrl(fn (Product $record): string => route('filament.admin.resources.products.view', $record))
        ;
    }
}
