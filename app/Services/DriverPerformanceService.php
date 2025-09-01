<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DriverPerformanceService
{
    /**
     * Get driver performance data for a period or specific shifts
     */
    public function getDriverPerformanceData(?string $startDate = null, ?string $endDate = null, ?array $shiftIds = null): Collection
    {
        if ($shiftIds && !empty($shiftIds)) {
            $ordersQuery = Order::query()
                ->whereIn('shift_id', $shiftIds)
                ->whereNotNull('driver_id');
        } else {
            $shiftsReportService = app(ShiftsReportService::class);
            $availableShiftIds = $shiftsReportService->getShiftsInPeriodQuery($startDate, $endDate, null)
                ->pluck('id')
                ->toArray();

            if (empty($availableShiftIds)) {
                return collect();
            }

            $ordersQuery = Order::query()
                ->whereIn('shift_id', $availableShiftIds)
                ->whereNotNull('driver_id');
        }

        return Driver::query()
            ->select([
                'drivers.*',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('COUNT(CASE WHEN orders.status = "completed" THEN 1 END) as completed_orders_count'),
                DB::raw('SUM(CASE WHEN orders.status = "completed" THEN orders.total ELSE 0 END) as total_value'),
                DB::raw('AVG(CASE WHEN orders.status = "completed" THEN orders.total ELSE NULL END) as avg_order_value'),
            ])
            ->leftJoinSub($ordersQuery, 'driver_orders', function ($join) {
                $join->on('drivers.id', '=', 'driver_orders.driver_id');
            })
            ->groupBy('drivers.id', 'drivers.name', 'drivers.phone', 'drivers.created_at', 'drivers.updated_at')
            ->havingRaw('COUNT(driver_orders.id) > 0')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    /**
     * Get detailed orders for a specific driver in a period or shifts
     */
    public function getDriverOrdersInPeriod(int $driverId, ?string $startDate = null, ?string $endDate = null, ?array $shiftIds = null)
    {
        $query = Order::query()
            ->with(['customer', 'user', 'shift', 'payments'])
            ->where('driver_id', $driverId);

        if ($shiftIds && !empty($shiftIds)) {
            $query->whereIn('shift_id', $shiftIds);
        } else {
            $shiftsReportService = app(ShiftsReportService::class);
            $availableShiftIds = $shiftsReportService->getShiftsInPeriodQuery($startDate, $endDate, null)
                ->pluck('id')
                ->toArray();

            if (!empty($availableShiftIds)) {
                $query->whereIn('shift_id', $availableShiftIds);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get summary statistics for driver performance
     */
    public function getDriverPerformanceSummary(?string $startDate = null, ?string $endDate = null, ?array $shiftIds = null): array
    {
        if ($shiftIds && !empty($shiftIds)) {
            $ordersQuery = Order::query()
                ->whereIn('shift_id', $shiftIds)
                ->whereNotNull('driver_id')
                ->where('status', OrderStatus::COMPLETED);
        } else {
            $shiftsReportService = app(ShiftsReportService::class);
            $availableShiftIds = $shiftsReportService->getShiftsInPeriodQuery($startDate, $endDate, null)
                ->pluck('id')
                ->toArray();

            if (empty($availableShiftIds)) {
                return [
                    'active_drivers' => 0,
                    'total_orders' => 0,
                    'total_value' => 0,
                    'avg_orders_per_driver' => 0,
                    'avg_value_per_driver' => 0,
                ];
            }

            $ordersQuery = Order::query()
                ->whereIn('shift_id', $availableShiftIds)
                ->whereNotNull('driver_id')
                ->where('status', OrderStatus::COMPLETED);
        }

        $stats = $ordersQuery
            ->select([
                DB::raw('COUNT(DISTINCT driver_id) as active_drivers'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_value'),
            ])
            ->first();

        $activeDrivers = $stats->active_drivers ?? 0;
        $totalOrders = $stats->total_orders ?? 0;
        $totalValue = $stats->total_value ?? 0;

        return [
            'active_drivers' => $activeDrivers,
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'avg_orders_per_driver' => $activeDrivers > 0 ? $totalOrders / $activeDrivers : 0,
            'avg_value_per_driver' => $activeDrivers > 0 ? $totalValue / $activeDrivers : 0,
        ];
    }
}
