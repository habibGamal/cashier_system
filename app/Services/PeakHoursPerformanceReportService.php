<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class PeakHoursPerformanceReportService
{
    public function getOrdersQuery(?string $startDate = null, ?string $endDate = null)
    {
        return Order::query()
            ->where('status', OrderStatus::COMPLETED)
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('orders.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('orders.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            });
    }

    /**
     * Get hourly performance analysis
     */
    public function getHourlyPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('HOUR(orders.created_at) as hour'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COALESCE(COUNT(DISTINCT orders.id) / COUNT(DISTINCT DATE(orders.created_at)), 0) as avg_orders_per_day'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                $item->hour_label = sprintf('%02d:00', $item->hour);
                $item->period = match (true) {
                    $item->hour >= 6 && $item->hour < 12 => 'صباح',
                    $item->hour >= 12 && $item->hour < 17 => 'ظهر',
                    $item->hour >= 17 && $item->hour < 21 => 'مساء',
                    default => 'ليل'
                };
                return $item;
            });
    }

    /**
     * Get daily performance analysis
     */
    public function getDailyPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('DAYOFWEEK(orders.created_at) as day_of_week'),
                DB::raw('DAYNAME(orders.created_at) as day_name'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('day_of_week', 'day_name')
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($item) {
                $item->day_label = match ($item->day_name) {
                    'Sunday' => 'الأحد',
                    'Monday' => 'الاثنين',
                    'Tuesday' => 'الثلاثاء',
                    'Wednesday' => 'الأربعاء',
                    'Thursday' => 'الخميس',
                    'Friday' => 'الجمعة',
                    'Saturday' => 'السبت',
                    default => $item->day_name
                };
                return $item;
            });
    }

    /**
     * Get weekly performance analysis
     */
    public function getWeeklyPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('YEAR(orders.created_at) as year'),
                DB::raw('WEEK(orders.created_at) as week'),
                DB::raw('MIN(DATE(orders.created_at)) as week_start'),
                DB::raw('MAX(DATE(orders.created_at)) as week_end'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT DATE(orders.created_at)) as active_days'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('year', 'week')
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->get()
            ->map(function ($item) {
                $item->week_label = sprintf('أسبوع %d من %d', $item->week, $item->year);
                $item->date_range = sprintf('%s - %s',
                    Carbon::parse($item->week_start)->format('d/m'),
                    Carbon::parse($item->week_end)->format('d/m')
                );
                return $item;
            });
    }

    /**
     * Get monthly performance analysis
     */
    public function getMonthlyPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('YEAR(orders.created_at) as year'),
                DB::raw('MONTH(orders.created_at) as month'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT DATE(orders.created_at)) as active_days'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                $monthNames = [
                    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                ];
                $item->month_label = sprintf('%s %d', $monthNames[$item->month], $item->year);
                return $item;
            });
    }

    /**
     * Get peak performance analysis by different time periods
     */
    public function getPeakAnalysis(?string $startDate = null, ?string $endDate = null): array
    {
        $hourlyData = $this->getHourlyPerformance($startDate, $endDate);
        $dailyData = $this->getDailyPerformance($startDate, $endDate);

        // Find peak hours
        $peakHour = $hourlyData->sortByDesc('total_sales')->first();
        $peakOrdersHour = $hourlyData->sortByDesc('total_orders')->first();

        // Find peak days
        $peakDay = $dailyData->sortByDesc('total_sales')->first();
        $peakOrdersDay = $dailyData->sortByDesc('total_orders')->first();

        // Calculate period performance
        $periodPerformance = [
            'صباح' => $hourlyData->where('period', 'صباح')->sum('total_sales'),
            'ظهر' => $hourlyData->where('period', 'ظهر')->sum('total_sales'),
            'مساء' => $hourlyData->where('period', 'مساء')->sum('total_sales'),
            'ليل' => $hourlyData->where('period', 'ليل')->sum('total_sales'),
        ];

        $bestPeriod = collect($periodPerformance)->sortDesc()->keys()->first();

        return [
            'peak_sales_hour' => $peakHour,
            'peak_orders_hour' => $peakOrdersHour,
            'peak_sales_day' => $peakDay,
            'peak_orders_day' => $peakOrdersDay,
            'period_performance' => $periodPerformance,
            'best_period' => $bestPeriod,
            'total_sales' => $hourlyData->sum('total_sales'),
            'total_orders' => $hourlyData->sum('total_orders'),
            'average_hourly_sales' => $hourlyData->avg('total_sales'),
            'average_hourly_orders' => $hourlyData->avg('total_orders'),
        ];
    }

    /**
     * Get performance by order type during different hours
     */
    public function getOrderTypeHourlyPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('HOUR(orders.created_at) as hour'),
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('hour', 'orders.type')
            ->orderBy('hour')
            ->orderBy('orders.type')
            ->get()
            ->map(function ($item) {
                $item->hour_label = sprintf('%02d:00', $item->hour);
                $item->type_label = $item->type->label();
                return $item;
            });
    }

    /**
     * Get customer traffic patterns
     */
    public function getCustomerTrafficPatterns(?string $startDate = null, ?string $endDate = null): array
    {
        $hourlyTraffic = $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('HOUR(orders.created_at) as hour'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Calculate customer density (orders per customer per hour)
        $trafficMetrics = $hourlyTraffic->map(function ($item) {
            $item->customer_density = $item->unique_customers > 0 ?
                $item->total_orders / $item->unique_customers : 0;
            $item->revenue_per_customer = $item->unique_customers > 0 ?
                $item->total_sales / $item->unique_customers : 0;
            return $item;
        });

        $peakTrafficHour = $trafficMetrics->sortByDesc('unique_customers')->first();
        $peakDensityHour = $trafficMetrics->sortByDesc('customer_density')->first();

        return [
            'hourly_traffic' => $trafficMetrics,
            'peak_traffic_hour' => $peakTrafficHour,
            'peak_density_hour' => $peakDensityHour,
            'total_unique_customers' => $hourlyTraffic->sum('unique_customers'),
            'average_hourly_customers' => $hourlyTraffic->avg('unique_customers'),
        ];
    }

    /**
     * Get staff optimization recommendations
     */
    public function getStaffOptimizationInsights(?string $startDate = null, ?string $endDate = null): array
    {
        $hourlyData = $this->getHourlyPerformance($startDate, $endDate);

        // Calculate workload score based on orders and sales
        $hourlyData = $hourlyData->map(function ($item) {
            $item->workload_score = ($item->total_orders * 0.6) + ($item->total_sales / 100 * 0.4);
            return $item;
        });

        // Categorize hours by workload
        $maxWorkload = $hourlyData->max('workload_score');
        $categories = $hourlyData->map(function ($item) use ($maxWorkload) {
            $percentage = $maxWorkload > 0 ? ($item->workload_score / $maxWorkload) * 100 : 0;

            $item->workload_category = match (true) {
                $percentage >= 80 => 'ذروة عالية',
                $percentage >= 60 => 'ذروة متوسطة',
                $percentage >= 40 => 'عادي',
                $percentage >= 20 => 'هادئ',
                default => 'هادئ جداً'
            };

            $item->staff_recommendation = match (true) {
                $percentage >= 80 => 'طاقم كامل + دعم إضافي',
                $percentage >= 60 => 'طاقم كامل',
                $percentage >= 40 => 'طاقم عادي',
                $percentage >= 20 => 'طاقم مخفف',
                default => 'حد أدنى من الطاقم'
            };

            return $item;
        });

        return [
            'hourly_workload' => $categories,
            'peak_hours' => $categories->where('workload_category', 'ذروة عالية'),
            'quiet_hours' => $categories->where('workload_category', 'هادئ جداً'),
            'recommendations' => $this->generateStaffRecommendations($categories),
        ];
    }

    /**
     * Generate staff recommendations based on workload analysis
     */
    private function generateStaffRecommendations($hourlyData): array
    {
        $recommendations = [];

        $peakHours = $hourlyData->where('workload_category', 'ذروة عالية');
        $quietHours = $hourlyData->where('workload_category', 'هادئ جداً');

        if ($peakHours->count() > 0) {
            $peakTime = $peakHours->pluck('hour_label')->implode('، ');
            $recommendations[] = "زيادة عدد الموظفين خلال ساعات الذروة: {$peakTime}";
        }

        if ($quietHours->count() > 0) {
            $quietTime = $quietHours->pluck('hour_label')->implode('، ');
            $recommendations[] = "تقليل عدد الموظفين خلال الساعات الهادئة: {$quietTime}";
        }

        $morningOrders = $hourlyData->whereBetween('hour', [6, 11])->sum('total_orders');
        $eveningOrders = $hourlyData->whereBetween('hour', [17, 22])->sum('total_orders');

        if ($eveningOrders > $morningOrders * 1.5) {
            $recommendations[] = "التركيز على تقوية الطاقم المسائي - حيث تزيد الطلبات بنسبة 50% عن الصباح";
        }

        return $recommendations;
    }

    /**
     * Get period info for display
     */
    public function getPeriodInfo(?string $startDate = null, ?string $endDate = null): array
    {
        if (!$startDate && !$endDate) {
            return [
                'title' => 'تقرير أداء ساعات الذروة - جميع الفترات',
                'description' => 'تحليل أنماط المبيعات والذروة عبر الساعات والأيام والأسابيع',
            ];
        }

        $start = $startDate ? Carbon::parse($startDate) : null;
        $end = $endDate ? Carbon::parse($endDate) : null;

        if ($start && $end) {
            return [
                'title' => 'تقرير أداء ساعات الذروة',
                'description' => sprintf(
                    'تحليل أنماط المبيعات والذروة من %s إلى %s',
                    $start->format('d/m/Y'),
                    $end->format('d/m/Y')
                ),
            ];
        } elseif ($start) {
            return [
                'title' => 'تقرير أداء ساعات الذروة',
                'description' => sprintf('تحليل أنماط المبيعات والذروة من %s حتى الآن', $start->format('d/m/Y')),
            ];
        } else {
            return [
                'title' => 'تقرير أداء ساعات الذروة',
                'description' => sprintf('تحليل أنماط المبيعات والذروة حتى %s', $end->format('d/m/Y')),
            ];
        }
    }
}
