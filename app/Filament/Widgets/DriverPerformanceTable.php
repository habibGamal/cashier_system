<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Filament\Resources\Drivers\Pages\ViewDriver;
use App\Models\Driver;
use App\Models\Order;
use App\Services\ShiftsReportService;
use App\Enums\OrderStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverPerformanceTable extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ShiftsReportService $shiftsReportService;


    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'تقرير أداء السائقين';

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getHeading(): string
    {
        return 'تقرير أداء السائقين';
    }

    public function table(Table $table): Table
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];
            $query = $this->getDriverPerformanceQuery($shiftIds, null, null);
        } else {
            $startDate = Carbon::parse($this->pageFilters['startDate'] ?? now()->subDays(6)->startOfDay()->toDateString())->startOfDay();
            $endDate = Carbon::parse($this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString())->endOfDay();

            $shiftIds = $this->shiftsReportService->getShiftsInPeriodQuery($startDate->toDateString(), $endDate->toDateString(), null)
                ->pluck('id')
                ->toArray();

            $query = $this->getDriverPerformanceQuery($shiftIds, $startDate, $endDate);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('اسم السائق')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),

                TextColumn::make('orders_count')
                    ->label('عدد الطلبات')
                    ->sortable()
                    ->default(0),

                TextColumn::make('completed_orders_count')
                    ->label('الطلبات المكتملة')
                    ->sortable()
                    ->default(0),

                TextColumn::make('total_value')
                    ->label('إجمالي القيمة')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ج.م'),

                TextColumn::make('avg_order_value')
                    ->label('متوسط قيمة الطلب')
                    ->sortable()
                    ->default(0)
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ج.م'),
            ])
            ->recordActions([
                Action::make('view_orders')
                    ->label('عرض الطلبات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function (Driver $record) use ($filterType) {
                        $shiftIdsParam = $filterType === 'shifts'
                            ? ($this->pageFilters['shifts'] ?? [])
                            : $this->getShiftIdsForPeriod();

                        $shiftIdsString = http_build_query(['tableFilters' => ['shift_ids' => ['values' => $shiftIdsParam]]]);

                        return url("/admin/drivers/{$record->id}?{$shiftIdsString}");
                    })
                    ->openUrlInNewTab(false),
            ])
            ->striped()
            ->paginated(false);
    }

    private function getDriverPerformanceQuery(array $shiftIds, ?Carbon $startDate = null, ?Carbon $endDate = null): Builder
    {
        if (empty($shiftIds)) {
            return Driver::query()->whereRaw('1 = 0'); // Return empty query
        }

        return Driver::query()
            ->select([
                'drivers.*',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('COUNT(CASE WHEN orders.status = "completed" THEN 1 END) as completed_orders_count'),
                DB::raw('SUM(CASE WHEN orders.status = "completed" THEN orders.total ELSE 0 END) as total_value'),
                DB::raw('AVG(CASE WHEN orders.status = "completed" THEN orders.total ELSE NULL END) as avg_order_value'),
            ])
            ->leftJoin('orders', function ($join) use ($shiftIds) {
                $join->on('drivers.id', '=', 'orders.driver_id')
                     ->whereIn('orders.shift_id', $shiftIds);
            })
            ->groupBy('drivers.id', 'drivers.name', 'drivers.phone', 'drivers.created_at', 'drivers.updated_at')
            ->havingRaw('COUNT(orders.id) > 0');
    }

    private function getShiftIdsForPeriod(): array
    {
        $startDate = Carbon::parse($this->pageFilters['startDate'] ?? now()->subDays(6)->startOfDay()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString())->endOfDay();

        return $this->shiftsReportService->getShiftsInPeriodQuery($startDate->toDateString(), $endDate->toDateString(), null)
            ->pluck('id')
            ->toArray();
    }
}
