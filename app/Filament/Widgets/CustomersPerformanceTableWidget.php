<?php

namespace App\Filament\Widgets;

use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use App\Services\CustomersPerformanceReportService;
use App\Filament\Exports\CustomersPerformanceTableExporter;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class CustomersPerformanceTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected static ?string $heading = 'تفاصيل أداء العملاء';

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    public function table(Table $table): Table
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        return $table
            ->query(
                $this->customersReportService->getCustomersPerformanceQuery(
                    $startDate,
                    $endDate
                )
            )
            ->headerActions([
                ExportAction::make()
                    ->label('تصدير تقرير أداء العملاء')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(CustomersPerformanceTableExporter::class)
                    ->fileName(fn() => 'customers-performance-' . now()->format('Y-m-d-H-i-s') . '.xlsx'),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('region')
                    ->label('المنطقة')
                    ->sortable()
                    ->default('غير محدد'),

                TextColumn::make('total_orders')
                    ->label('عدد الطلبات')
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

                TextColumn::make('avg_order_value')
                    ->label('متوسط قيمة الطلب')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('profit_margin')
                    ->label('هامش الربح %')
                    ->state(
                        fn($record) => $record->total_sales > 0
                        ? number_format(($record->total_profit / $record->total_sales) * 100, 1) . '%'
                        : '0%'
                    ),

                TextColumn::make('customer_segment')
                    ->label('تصنيف العميل')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'VIP' => 'warning',
                        'مخلص' => 'success',
                        'عادي' => 'info',
                        'جديد' => 'gray',
                    }),

                TextColumn::make('last_order_date')
                    ->label('آخر طلب')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('first_order_date')
                    ->label('أول طلب')
                    ->date('Y-m-d')
                    ->sortable(),

                // Order Type Performance
                TextColumn::make('dine_in_sales')
                    ->label('صالة')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('takeaway_sales')
                    ->label('تيك أواي')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('delivery_sales')
                    ->label('دليفري')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('web_delivery_sales')
                    ->label('اونلاين دليفري')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('web_takeaway_sales')
                    ->label('اونلاين تيك أواي')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('talabat_sales')
                    ->label('طلبات')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                // TextColumn::make('companies_sales')
                //     ->label('شركات')
                //     ->money('EGP')
                //     ->sortable()
                //     ->toggleable(),
            ])
            ->defaultSort('total_sales', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->recordActions([
                ViewAction::make()
                    ->label('عرض العميل')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => route('filament.admin.resources.customers.view', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->recordAction(ViewAction::class)
            ->recordUrl(fn ($record): string => route('filament.admin.resources.customers.view', $record->id))
            ->filters(
                [
                    // Tables\Filters\SelectFilter::make('customer_segment')
                    //     ->label('تصنيف العميل')
                    //     ->options([
                    //         'VIP' => 'VIP',
                    //         'مخلص' => 'مخلص',
                    //         'عادي' => 'عادي',
                    //         'جديد' => 'جديد',
                    //     ])
                    //     ->modifyBaseQueryUsing(function (Builder $query, array $data): Builder {
                    //         return $query->when(
                    //             $data['value'],
                    //             function (Builder $query, $segment): Builder {
                    //                 return $query->havingRaw(
                    //                     'CASE
                    //                         WHEN COALESCE(SUM(order_items.total), 0) >= 5000 AND COALESCE(COUNT(DISTINCT orders.id), 0) >= 20 THEN "VIP"
                    //                         WHEN COALESCE(SUM(order_items.total), 0) >= 2000 AND COALESCE(COUNT(DISTINCT orders.id), 0) >= 10 THEN "مخلص"
                    //                         WHEN COALESCE(COUNT(DISTINCT orders.id), 0) >= 5 THEN "عادي"
                    //                         ELSE "جديد"
                    //                     END = ?',
                    //                     [$segment]
                    //                 );
                    //             }
                    //         );
                    //     }),
                ]
            );
    }
}
