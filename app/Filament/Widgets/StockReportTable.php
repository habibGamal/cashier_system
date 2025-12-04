<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use App\Filament\Exports\StockReportExporter;
use App\Models\Product;
use App\Models\Category;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Enums\ProductType;

class StockReportTable extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'تقرير المخزون';

    public function table(Table $table): Table
    {
        $startDate = $this->pageFilters['startDate'] ?? '2025-07-29';
        $endDate = $this->pageFilters['endDate'] ?? '2025-08-1';

        return $table
            ->query(
                Product::query()
                    ->whereNot('type', ProductType::Manufactured)
                    ->with(['category'])
                    ->addSelect([
                        'products.id',
                        'products.name',
                        'products.category_id',
                        'products.cost',
                        'inventory_items.id as inventory_item_id',
                        'inventory_items.quantity as actual_remaining_quantity',
                        // end quantity of the previous day
                        DB::raw(
                            "(
                            SELECT COALESCE(dm.end_quantity, 0)
                            FROM inventory_item_movement_daily dm
                            WHERE dm.product_id = products.id
                            AND dm.date = (
                                SELECT MAX(d.date)
                                FROM inventory_item_movement_daily d
                                WHERE d.product_id = products.id
                                    AND d.date < '{$startDate}'
                            )
                            ORDER BY dm.id DESC
                            LIMIT 1
                        ) AS start_quantity"
                        ),
                        DB::raw('COALESCE(SUM(dailyMovements.incoming_quantity), 0) as incoming'),
                        DB::raw('COALESCE(SUM(dailyMovements.sales_quantity), 0) - COALESCE(SUM(dailyMovements.return_sales_quantity), 0) as sales'),
                        DB::raw('COALESCE(SUM(dailyMovements.return_waste_quantity), 0) as return_waste'),
                        // DB::raw('COALESCE(SUM(dailyMovements.return_sales_quantity), 0) as return_sales'),
                        // Created at of startDailyMovements

                        DB::raw("(SELECT dm.created_at
                                            FROM inventory_item_movement_daily dm
                                            WHERE dm.product_id = products.id
                                                AND dm.date BETWEEN '{$startDate}' AND '{$endDate}'
                                            ORDER BY dm.date ASC, dm.id ASC
                                            LIMIT 1) as start_created_at"),

                        // Last closed_at using subquery (will return NULL if the last record is NULL)
                        DB::raw("(SELECT dm.closed_at
                                            FROM inventory_item_movement_daily dm
                                            WHERE dm.product_id = products.id
                                                AND dm.date BETWEEN '{$startDate}' AND '{$endDate}'
                                            ORDER BY dm.date DESC, dm.id DESC
                                            LIMIT 1) as last_closed_at"),
                    ])
                    ->leftJoin('inventory_items', function ($join) {
                        $join->on('products.id', '=', 'inventory_items.product_id');
                    })
                    ->leftJoin('inventory_item_movement_daily as dailyMovements', function ($join) use ($startDate, $endDate) {
                        $join->on('products.id', '=', 'dailyMovements.product_id')
                            ->whereBetween('dailyMovements.date', [$startDate, $endDate]);
                    })
                    ->groupBy([
                        'products.id',
                        'products.name',
                        'products.category_id',
                        'products.cost',
                        'inventory_items.quantity',
                        'inventory_items.id',
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('المنتج')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary'),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable()
                    ->color('gray'),

                TextColumn::make('start_quantity')
                    ->label('الكمية البدائية')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->color(function ($state) {
                        return $state > 0 ? 'success' : 'gray';
                    })
                    ->alignCenter(),

                TextColumn::make('incoming')
                    ->label('كمية الوارد')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->toggleable()
                    ->color(function ($state) {
                        return $state > 0 ? 'info' : 'gray';
                    })
                    ->alignCenter(),

                // Tables\Columns\TextColumn::make('return_sales')
                //     ->label('مرتجع المبيعات')
                //     ->numeric()
                //     ->sortable()
                //     ->default(0)
                //     ->toggleable()
                //     ->color(function ($state) { return $state > 0 ? 'warning' : 'gray'; })
                //     ->alignCenter(),

                TextColumn::make('total_quantity')
                    ->label('الكمية الكلية')
                    ->numeric()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                    })
                    ->color('success')
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('sales')
                    ->label('كمية المبيعات')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->toggleable()
                    ->color(function ($state) {
                        return $state > 0 ? 'danger' : 'gray';
                    })
                    ->alignCenter(),

                TextColumn::make('return_waste')
                    ->label('كمية الفاقد والمرتجع')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->toggleable()
                    ->color(function ($state) {
                        return $state > 0 ? 'danger' : 'gray';
                    })
                    ->alignCenter(),

                TextColumn::make('total_consumed')
                    ->label('الكمية الكلية المنصرفة')
                    ->numeric()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return ($record->sales ?? 0) + ($record->return_waste ?? 0);
                    })
                    ->color('danger')
                    ->weight('medium')
                    ->alignCenter(),

                TextColumn::make('ideal_remaining')
                    ->label('الكمية المتبقية المثالية')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                        $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                        return $totalQuantity - $totalConsumed;
                    })
                    ->color('primary')
                    ->weight('medium')
                    ->alignCenter(),

                TextColumn::make('actual_remaining_quantity')
                    ->label('الكمية المتبقية الفعلية')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->default(0)
                    ->color(function ($state, $record) {
                        $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                        $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                        $idealRemaining = $totalQuantity - $totalConsumed;

                        if ($state == $idealRemaining) {
                            return 'success';
                        } elseif ($state < $idealRemaining) {
                            return 'danger';
                        } else {
                            return 'warning';
                        }
                    })
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('cost')
                    ->label('متوسط التكلفة')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color('info')
                    ->toggleable()
                    ->suffix(' جنيه')
                    ->alignCenter(),

                TextColumn::make('deviation')
                    ->label('الانحراف')
                    ->numeric()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                        $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                        $idealRemaining = $totalQuantity - $totalConsumed;
                        $actualRemaining = $record->actual_remaining_quantity ?? 0;

                        return $actualRemaining - $idealRemaining;
                    })
                    ->color(function ($state) {
                        if ($state == 0)
                            return 'success';
                        return $state < 0 ? 'danger' : 'warning';
                    })
                    ->weight('medium')
                    ->alignCenter(),

                TextColumn::make('deviation_value')
                    ->label('قيمة الانحراف')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                        $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                        $idealRemaining = $totalQuantity - $totalConsumed;
                        $actualRemaining = $record->actual_remaining_quantity ?? 0;
                        $deviation = $actualRemaining - $idealRemaining;

                        return abs($deviation) * ($record->cost ?? 0);
                    })
                    ->color(function ($state) {
                        return $state > 0 ? 'danger' : 'success';
                    })
                    ->suffix(' جنيه')
                    ->alignCenter(),

                TextColumn::make('deviation_percentage')
                    ->label('نسبة الانحراف')
                    ->numeric(decimalPlaces: 1)
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $totalQuantity = ($record->start_quantity ?? 0) + ($record->incoming ?? 0);
                        $totalConsumed = ($record->sales ?? 0) + ($record->return_waste ?? 0);
                        $idealRemaining = $totalQuantity - $totalConsumed;
                        $actualRemaining = $record->actual_remaining_quantity ?? 0;
                        $deviation = $actualRemaining - $idealRemaining;

                        if ($idealRemaining == 0)
                            return 0;
                        return ($deviation / $idealRemaining) * 100;
                    })
                    ->color(function ($state) {
                        if (abs($state) <= 5)
                            return 'success';
                        if (abs($state) <= 15)
                            return 'warning';
                        return 'danger';
                    })
                    ->suffix('%')
                    ->weight('medium')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('has_stock')
                    ->label('المنتجات المتوفرة في المخزن')
                    ->queries(
                        true: function (Builder $query) {
                            return $query->whereHas('inventoryItem', function ($q) {
                                $q->where('quantity', '>', 0);
                            });
                        },
                        false: function (Builder $query) {
                            return $query->whereDoesntHave('inventoryItem')
                                ->orWhereHas('inventoryItem', function ($q) {
                                    $q->where('quantity', '<=', 0);
                                });
                        },
                        blank: function (Builder $query) {
                            return $query;
                        },
                    ),

                TernaryFilter::make('has_movements')
                    ->label('المنتجات التي لها حركة في الفترة')
                    ->queries(
                        true: function (Builder $query) use ($startDate, $endDate) {
                            return $query->whereHas('dailyMovements', function ($q) use ($startDate, $endDate) {
                                $q->whereBetween('date', [$startDate, $endDate]);
                            });
                        },
                        false: function (Builder $query) use ($startDate, $endDate) {
                            return $query->whereDoesntHave('dailyMovements', function ($q) use ($startDate, $endDate) {
                                $q->whereBetween('date', [$startDate, $endDate]);
                            });
                        },
                        blank: function (Builder $query) {
                            return $query;
                        },
                    ),

                SelectFilter::make('cost_range')
                    ->label('نطاق التكلفة')
                    ->options([
                        'low' => 'منخفضة (أقل من 10 جنيه)',
                        'medium' => 'متوسطة (10 - 50 جنيه)',
                        'high' => 'عالية (أكثر من 50 جنيه)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'low',
                            function (Builder $query): Builder {
                                return $query->where('products.cost', '<', 10);
                            },
                        )->when(
                                $data['value'] === 'medium',
                                function (Builder $query): Builder {
                                    return $query->whereBetween('products.cost', [10, 50]);
                                },
                            )->when(
                                $data['value'] === 'high',
                                function (Builder $query): Builder {
                                    return $query->where('products.cost', '>', 50);
                                },
                            );
                    }),

                Filter::make('zero_cost')
                    ->label('المنتجات بدون تكلفة محددة')
                    ->query(function (Builder $query): Builder {
                        return $query->whereNull('products.cost')->orWhere('products.cost', 0);
                    }),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->emptyStateHeading('لا توجد منتجات')
            ->emptyStateDescription('لم يتم العثور على أي منتجات لعرضها في التقرير.')
            ->emptyStateIcon('heroicon-o-cube')
            ->recordActions([
                Action::make('view_product')
                    ->label('عرض المنتج')
                    ->icon('heroicon-o-eye')
                    ->url(function ($record) {
                        return route('filament.admin.resources.inventory-items.view', [
                            'record' => $record->inventory_item_id,
                            'tableFilters' => [
                                'created_at' => [
                                    'created_from' => $record->start_created_at,
                                    'created_until' => $record->last_closed_at,
                                ],
                            ],
                        ]);
                    })
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير التقرير')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(StockReportExporter::class)
                    // ->modifyQueryUsing(function (Builder $query) use ($startDate, $endDate) {
                    //     return $query;
                    // })
                    ->fileName(fn() => 'stock-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx')
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->toolbarActions([]);
    }
}
