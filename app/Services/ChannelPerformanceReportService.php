<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ChannelPerformanceReportService
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
     * Get overall channel performance summary
     */
    public function getChannelPerformanceSummary(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                DB::raw('COALESCE(SUM(order_items.total) / COUNT(DISTINCT orders.customer_id), 0) as avg_revenue_per_customer'),
                DB::raw('COALESCE(COUNT(DISTINCT orders.id) / COUNT(DISTINCT orders.customer_id), 0) as avg_orders_per_customer'),
                // Profit margin calculation
                DB::raw('CASE
                    WHEN COALESCE(SUM(order_items.total), 0) > 0
                    THEN (COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) / COALESCE(SUM(order_items.total), 1)) * 100
                    ELSE 0
                END as profit_margin_percentage'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.type')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function ($item) {
                $item->type_label = $item->type->label();
                $item->market_share = 0; // Will be calculated later
                return $item;
            });
    }

    /**
     * Get channel performance trends over time
     */
    public function getChannelTrends(?string $startDate = null, ?string $endDate = null, string $period = 'daily'): Collection
    {
        $dateFormat = match ($period) {
            'hourly' => '%Y-%m-%d %H:00:00',
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw("DATE_FORMAT(orders.created_at, '{$dateFormat}') as period"),
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('period', 'orders.type')
            ->orderBy('period')
            ->orderBy('orders.type')
            ->get()
            ->map(function ($item) use ($period) {
                $item->type_label = $item->type->label();
                $item->period_label = $this->formatPeriodLabel($item->period, $period);
                return $item;
            });
    }

    /**
     * Get customer acquisition by channel
     */
    public function getCustomerAcquisitionByChannel(?string $startDate = null, ?string $endDate = null): Collection
    {
        return DB::table('customers')
            ->select([
                DB::raw('first_order.type as acquisition_channel'),
                DB::raw('COUNT(DISTINCT customers.id) as customers_acquired'),
                DB::raw('COALESCE(AVG(customer_stats.total_orders), 0) as avg_lifetime_orders'),
                DB::raw('COALESCE(AVG(customer_stats.total_spent), 0) as avg_lifetime_value'),
                DB::raw('COALESCE(AVG(customer_stats.avg_order_value), 0) as avg_order_value'),
            ])
            ->joinSub(
                // Get first order for each customer
                Order::select([
                    'customer_id',
                    'type',
                    DB::raw('MIN(created_at) as first_order_date')
                ])
                ->where('status', OrderStatus::COMPLETED)
                ->whereNotNull('customer_id')
                ->groupBy('customer_id', 'type'),
                'first_order',
                'customers.id',
                '=',
                'first_order.customer_id'
            )
            ->joinSub(
                // Get customer lifetime stats
                $this->getOrdersQuery($startDate, $endDate)
                    ->select([
                        'orders.customer_id',
                        DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                        DB::raw('COALESCE(SUM(order_items.total), 0) as total_spent'),
                        DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
                    ])
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->whereNotNull('orders.customer_id')
                    ->groupBy('orders.customer_id'),
                'customer_stats',
                'customers.id',
                '=',
                'customer_stats.customer_id'
            )
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('customers.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('customers.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            })
            ->groupBy('first_order.type')
            ->orderByDesc('customers_acquired')
            ->get()
            ->map(function ($item) {
                $item->channel_label = OrderType::from($item->acquisition_channel)->label();
                return $item;
            });
    }

    /**
     * Get cross-channel customer behavior
     */
    public function getCrossChannelBehavior(?string $startDate = null, ?string $endDate = null): array
    {
        // Get customers who use multiple channels
        $multiChannelCustomers = $this->getOrdersQuery($startDate, $endDate)
            ->select([
                'orders.customer_id',
                DB::raw('COUNT(DISTINCT orders.type) as channels_used'),
                DB::raw('GROUP_CONCAT(DISTINCT orders.type) as channel_list'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_spent'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.customer_id')
            ->groupBy('orders.customer_id')
            ->having('channels_used', '>', 1)
            ->get();

        // Calculate channel switching patterns
        $channelSwitching = $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('LAG(orders.type) OVER (PARTITION BY orders.customer_id ORDER BY orders.created_at) as previous_channel'),
                'orders.type as current_channel',
                DB::raw('COUNT(*) as switch_count'),
            ])
            ->whereNotNull('orders.customer_id')
            ->groupBy('previous_channel', 'current_channel')
            ->havingRaw('previous_channel IS NOT NULL')
            ->havingRaw('previous_channel != current_channel')
            ->get();

        $totalMultiChannel = $multiChannelCustomers->count();
        $avgChannelsPerCustomer = $multiChannelCustomers->avg('channels_used');
        $multiChannelRevenue = $multiChannelCustomers->sum('total_spent');

        return [
            'multi_channel_customers' => $totalMultiChannel,
            'avg_channels_per_customer' => $avgChannelsPerCustomer,
            'multi_channel_revenue' => $multiChannelRevenue,
            'channel_switching_patterns' => $channelSwitching,
            'multi_channel_customers_detail' => $multiChannelCustomers,
        ];
    }

    /**
     * Get channel profitability analysis
     */
    public function getChannelProfitabilityAnalysis(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(order_items.cost * order_items.quantity), 0) as total_cost'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as gross_profit'),
                // Calculate estimated operational costs (could be enhanced with actual data)
                DB::raw('CASE
                    WHEN orders.type IN ("delivery", "web_delivery", "talabat") THEN COALESCE(SUM(order_items.total), 0) * 0.15
                    WHEN orders.type IN ("dine_in") THEN COALESCE(SUM(order_items.total), 0) * 0.25
                    ELSE COALESCE(SUM(order_items.total), 0) * 0.10
                END as estimated_operational_cost'),
                DB::raw('COALESCE(AVG(order_items.total), 0) as avg_order_value'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.type')
            ->orderByDesc('gross_profit')
            ->get()
            ->map(function ($item) {
                $item->type_label = $item->type->label();
                $item->net_profit = $item->gross_profit - $item->estimated_operational_cost;
                $item->gross_margin = $item->total_revenue > 0 ?
                    ($item->gross_profit / $item->total_revenue) * 100 : 0;
                $item->net_margin = $item->total_revenue > 0 ?
                    ($item->net_profit / $item->total_revenue) * 100 : 0;
                $item->profit_per_order = $item->total_orders > 0 ?
                    $item->net_profit / $item->total_orders : 0;
                return $item;
            });
    }

    /**
     * Get channel performance by time of day
     */
    public function getChannelPerformanceByHour(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('HOUR(orders.created_at) as hour'),
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
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
     * Get channel efficiency metrics
     */
    public function getChannelEfficiencyMetrics(?string $startDate = null, ?string $endDate = null): array
    {
        $channelData = $this->getChannelPerformanceSummary($startDate, $endDate);

        $totalRevenue = $channelData->sum('total_sales');
        $totalOrders = $channelData->sum('total_orders');
        $totalCustomers = $channelData->sum('unique_customers');

        // Calculate market share and efficiency metrics
        $channelData = $channelData->map(function ($item) use ($totalRevenue, $totalOrders, $totalCustomers) {
            $item->market_share = $totalRevenue > 0 ? ($item->total_sales / $totalRevenue) * 100 : 0;
            $item->order_share = $totalOrders > 0 ? ($item->total_orders / $totalOrders) * 100 : 0;
            $item->customer_share = $totalCustomers > 0 ? ($item->unique_customers / $totalCustomers) * 100 : 0;

            // Efficiency score (revenue per order relative to average)
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $item->efficiency_score = $avgOrderValue > 0 ? ($item->avg_order_value / $avgOrderValue) * 100 : 0;

            return $item;
        });

        $topChannelByRevenue = $channelData->sortByDesc('total_sales')->first();
        $topChannelByOrders = $channelData->sortByDesc('total_orders')->first();
        $mostEfficientChannel = $channelData->sortByDesc('efficiency_score')->first();

        return [
            'channel_summary' => $channelData,
            'top_channel_by_revenue' => $topChannelByRevenue,
            'top_channel_by_orders' => $topChannelByOrders,
            'most_efficient_channel' => $mostEfficientChannel,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'avg_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
        ];
    }

    /**
     * Format period label based on period type
     */
    private function formatPeriodLabel(string $period, string $periodType): string
    {
        return match ($periodType) {
            'hourly' => Carbon::parse($period)->format('d/m H:i'),
            'daily' => Carbon::parse($period)->format('d/m/Y'),
            'weekly' => 'أسبوع ' . $period,
            'monthly' => Carbon::createFromFormat('Y-m', $period)->format('M Y'),
            default => $period
        };
    }

    /**
     * Get period info for display
     */
    public function getPeriodInfo(?string $startDate = null, ?string $endDate = null): array
    {
        if (!$startDate && !$endDate) {
            return [
                'title' => 'تقرير أداء القنوات - جميع الفترات',
                'description' => 'تحليل أداء جميع قنوات البيع والمقارنة بينها',
            ];
        }

        $start = $startDate ? Carbon::parse($startDate) : null;
        $end = $endDate ? Carbon::parse($endDate) : null;

        if ($start && $end) {
            return [
                'title' => 'تقرير أداء القنوات',
                'description' => sprintf(
                    'تحليل أداء قنوات البيع من %s إلى %s',
                    $start->format('d/m/Y'),
                    $end->format('d/m/Y')
                ),
            ];
        } elseif ($start) {
            return [
                'title' => 'تقرير أداء القنوات',
                'description' => sprintf('تحليل أداء قنوات البيع من %s حتى الآن', $start->format('d/m/Y')),
            ];
        } else {
            return [
                'title' => 'تقرير أداء القنوات',
                'description' => sprintf('تحليل أداء قنوات البيع حتى %s', $end->format('d/m/Y')),
            ];
        }
    }
}
