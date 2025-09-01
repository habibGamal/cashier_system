<?php

namespace App\Filament\Widgets;

use App\Services\ShiftsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;
use App\Models\Driver;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class DriverPerformanceStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ShiftsReportService $shiftsReportService;

    public function boot(): void
    {
        $this->shiftsReportService = app(ShiftsReportService::class);
    }

    public function getHeading(): string
    {
        return 'إحصائيات أداء السائقين';
    }

    protected function getStats(): array
    {
        $driverStats = $this->calculateDriverStats();

        return [
            Stat::make('إجمالي السائقين النشطين', $driverStats['active_drivers'])
                ->description('السائقين الذين لديهم طلبات في الفترة')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('إجمالي الطلبات', number_format($driverStats['total_orders']))
                ->description('جميع طلبات التوصيل في الفترة')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('إجمالي قيمة الطلبات', number_format($driverStats['total_value'], 2) . ' ج.م')
                ->description('القيمة الإجمالية لطلبات التوصيل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('متوسط الطلبات لكل سائق', number_format($driverStats['avg_orders_per_driver'], 1))
                ->description('متوسط عدد الطلبات لكل سائق')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }

    private function calculateDriverStats(): array
    {
        $filterType = $this->pageFilters['filterType'] ?? 'period';

        if ($filterType === 'shifts') {
            $shiftIds = $this->pageFilters['shifts'] ?? [];

            if (empty($shiftIds)) {
                return [
                    'active_drivers' => 0,
                    'total_orders' => 0,
                    'total_value' => 0,
                    'avg_orders_per_driver' => 0,
                ];
            }

            $query = Order::query()
                ->whereIn('shift_id', $shiftIds)
                ->whereNotNull('driver_id')
                ->where('status', OrderStatus::COMPLETED);
        } else {
            $startDate = Carbon::parse($this->pageFilters['startDate'] ?? now()->subDays(6)->startOfDay()->toDateString())->startOfDay();
            $endDate = Carbon::parse($this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString())->endOfDay();

            $shiftIds = $this->shiftsReportService->getShiftsInPeriodQuery($startDate->toDateString(), $endDate->toDateString(), null)
                ->pluck('id')
                ->toArray();

            if (empty($shiftIds)) {
                return [
                    'active_drivers' => 0,
                    'total_orders' => 0,
                    'total_value' => 0,
                    'avg_orders_per_driver' => 0,
                ];
            }

            $query = Order::query()
                ->whereIn('shift_id', $shiftIds)
                ->whereNotNull('driver_id')
                ->where('status', OrderStatus::COMPLETED);
        }

        $stats = $query
            ->select([
                DB::raw('COUNT(DISTINCT driver_id) as active_drivers'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_value'),
            ])
            ->first();

        $activeDrivers = $stats->active_drivers ?? 0;
        $totalOrders = $stats->total_orders ?? 0;
        $totalValue = $stats->total_value ?? 0;
        $avgOrdersPerDriver = $activeDrivers > 0 ? $totalOrders / $activeDrivers : 0;

        return [
            'active_drivers' => $activeDrivers,
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'avg_orders_per_driver' => $avgOrdersPerDriver,
        ];
    }
}
