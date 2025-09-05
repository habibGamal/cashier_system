<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnOrder;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class CustomersPerformanceReportService
{
    public function getOrdersQuery(?string $startDate = null, ?string $endDate = null)
    {
        return Order::query()
            ->where('status', OrderStatus::COMPLETED)
            ->whereNotNull('orders.customer_id')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('orders.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('orders.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            });
    }

    public function getCustomersPerformanceQuery(?string $startDate = null, ?string $endDate = null)
    {
        // Get customers with sales data aggregated by order type
        return Customer::query()
            ->select([
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.region',
                'customers.delivery_cost',
                // Total sales across all order types
                DB::raw('COALESCE(COUNT(DISTINCT orders.id), 0) as total_orders'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
                DB::raw('COALESCE(CASE WHEN COUNT(DISTINCT orders.id) > 0 THEN SUM(order_items.total) / COUNT(DISTINCT orders.id) ELSE 0 END) as avg_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date'),
                DB::raw('MIN(orders.created_at) as first_order_date'),

                // Takeaway
                DB::raw('COALESCE(COUNT(DISTINCT CASE WHEN orders.type = "takeaway" THEN orders.id END), 0) as takeaway_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "takeaway" THEN order_items.total ELSE 0 END), 0) as takeaway_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "takeaway" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as takeaway_profit'),

                // Delivery
                DB::raw('COALESCE(COUNT(DISTINCT CASE WHEN orders.type = "delivery" THEN orders.id END), 0) as delivery_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "delivery" THEN order_items.total ELSE 0 END), 0) as delivery_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "delivery" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as delivery_profit'),

                // Web Delivery
                DB::raw('COALESCE(COUNT(DISTINCT CASE WHEN orders.type = "web_delivery" THEN orders.id END), 0) as web_delivery_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_delivery" THEN order_items.total ELSE 0 END), 0) as web_delivery_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_delivery" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as web_delivery_profit'),

                // Web Takeaway
                DB::raw('COALESCE(COUNT(DISTINCT CASE WHEN orders.type = "web_takeaway" THEN orders.id END), 0) as web_takeaway_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_takeaway" THEN order_items.total ELSE 0 END), 0) as web_takeaway_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_takeaway" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as web_takeaway_profit'),

                // Customer Segment
                DB::raw('CASE
                    WHEN COALESCE(SUM(order_items.total), 0) >= 5000 AND COALESCE(COUNT(DISTINCT orders.id), 0) >= 20 THEN "VIP"
                    WHEN COALESCE(SUM(order_items.total), 0) >= 2000 AND COALESCE(COUNT(DISTINCT orders.id), 0) >= 10 THEN "مخلص"
                    WHEN COALESCE(COUNT(DISTINCT orders.id), 0) >= 5 THEN "عادي"
                    ELSE "جديد"
                END as customer_segment'),
            ])
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::COMPLETED)
                    ->when($startDate, function ($query) use ($startDate) {
                        $query->where('orders.created_at', '>=', Carbon::parse($startDate)->startOfDay());
                    })
                    ->when($endDate, function ($query) use ($endDate) {
                        $query->where('orders.created_at', '<=', Carbon::parse($endDate)->endOfDay());
                    });
            })
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.region',
                'customers.delivery_cost'
            );
    }

    /**
     * Get customers performance within a date range
     */
    public function getCustomersPerformance(?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->getCustomersPerformanceQuery($startDate, $endDate)->get();
    }

    /**
     * Get period statistics summary
     */
    public function getPeriodSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = $this->getOrdersQuery($startDate, $endDate)
            ->select([
                DB::raw('COUNT(DISTINCT customers.id) as total_customers'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.total) as total_sales'),
                DB::raw('SUM(order_items.total - (order_items.cost * order_items.quantity)) as total_profit'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('CASE WHEN COUNT(DISTINCT orders.id) > 0 THEN SUM(order_items.total) / COUNT(DISTINCT orders.id) ELSE 0 END as avg_order_value'),
                DB::raw('CASE WHEN COUNT(DISTINCT customers.id) > 0 THEN COUNT(DISTINCT orders.id) / COUNT(DISTINCT customers.id) ELSE 0 END as avg_orders_per_customer'),
            ])
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->first();

        // Get top customer by sales
        $topCustomerBySales = $this->getCustomersPerformanceQuery($startDate, $endDate)
            ->orderByDesc('total_sales')
            ->limit(1)
            ->first();

        // Get top customer by profit
        $topCustomerByProfit = $this->getCustomersPerformanceQuery($startDate, $endDate)
            ->orderByDesc('total_profit')
            ->limit(1)
            ->first();

        // Get most frequent customer (by order count)
        $mostFrequentCustomer = $this->getCustomersPerformanceQuery($startDate, $endDate)
            ->orderByDesc('total_orders')
            ->limit(1)
            ->first();

        // Get customer with highest average order value
        $highestAvgOrderCustomer = $this->getCustomersPerformanceQuery($startDate, $endDate)
            ->orderByDesc('avg_order_value')
            ->limit(1)
            ->first();

        if (!$summary) {
            return [
                'total_customers' => 0,
                'total_orders' => 0,
                'total_sales' => 0,
                'total_profit' => 0,
                'total_quantity' => 0,
                'avg_order_value' => 0,
                'avg_orders_per_customer' => 0,
                'top_customer_by_sales' => null,
                'top_customer_by_profit' => null,
                'most_frequent_customer' => null,
                'highest_avg_order_customer' => null,
            ];
        }

        return [
            'total_customers' => $summary->total_customers ?? 0,
            'total_orders' => $summary->total_orders ?? 0,
            'total_sales' => $summary->total_sales ?? 0,
            'total_profit' => $summary->total_profit ?? 0,
            'total_quantity' => $summary->total_quantity ?? 0,
            'avg_order_value' => $summary->avg_order_value ?? 0,
            'avg_orders_per_customer' => $summary->avg_orders_per_customer ?? 0,
            'top_customer_by_sales' => $topCustomerBySales,
            'top_customer_by_profit' => $topCustomerByProfit,
            'most_frequent_customer' => $mostFrequentCustomer,
            'highest_avg_order_customer' => $highestAvgOrderCustomer,
        ];
    }

    /**
     * Get order type performance for customers
     */
    public function getOrderTypePerformance(?string $startDate = null, ?string $endDate = null): array
    {
        $performanceData = $this->getOrdersQuery($startDate, $endDate)
            ->select([
                'orders.type',
                DB::raw('COUNT(DISTINCT orders.customer_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_sales'),
                DB::raw('SUM(order_items.total - (order_items.cost * order_items.quantity)) as total_profit'),
                DB::raw('AVG(order_items.total) as avg_order_value'),
            ])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.type')
            ->get();

        return $performanceData->mapWithKeys(function ($item) {
            return [
                $item->type->value => [
                    'label' => $item->type->label(),
                    'unique_customers' => $item->unique_customers,
                    'total_orders' => $item->total_orders,
                    'total_quantity' => $item->total_quantity,
                    'total_sales' => $item->total_sales,
                    'total_profit' => $item->total_profit,
                    'avg_order_value' => $item->avg_order_value,
                ],
            ];
        })->toArray();
    }

    /**
     * Get customer segments based on their performance
     */
    public function getCustomerSegments(?string $startDate = null, ?string $endDate = null): array
    {
        $customers = $this->getCustomersPerformanceQuery($startDate, $endDate)->get();

        $segments = [
            'vip' => ['count' => 0, 'total_sales' => 0, 'total_profit' => 0],
            'loyal' => ['count' => 0, 'total_sales' => 0, 'total_profit' => 0],
            'regular' => ['count' => 0, 'total_sales' => 0, 'total_profit' => 0],
            'new' => ['count' => 0, 'total_sales' => 0, 'total_profit' => 0],
        ];

        foreach ($customers as $customer) {
            // Segment customers based on their total sales and order frequency
            if ($customer->total_sales >= 5000 && $customer->total_orders >= 20) {
                $segment = 'vip';
            } elseif ($customer->total_sales >= 2000 && $customer->total_orders >= 10) {
                $segment = 'loyal';
            } elseif ($customer->total_orders >= 5) {
                $segment = 'regular';
            } else {
                $segment = 'new';
            }

            $segments[$segment]['count']++;
            $segments[$segment]['total_sales'] += $customer->total_sales;
            $segments[$segment]['total_profit'] += $customer->total_profit;
        }

        return $segments;
    }

    /**
     * Get period info for display
     */
    public function getPeriodInfo(?string $startDate = null, ?string $endDate = null): array
    {
        if (!$startDate && !$endDate) {
            return [
                'title' => 'تقرير أداء العملاء - جميع الفترات',
                'description' => 'أداء المبيعات والأرباح لجميع العملاء عبر جميع أنواع الطلبات',
            ];
        }

        $start = $startDate ? Carbon::parse($startDate) : null;
        $end = $endDate ? Carbon::parse($endDate) : null;

        if ($start && $end) {
            return [
                'title' => 'تقرير أداء العملاء',
                'description' => sprintf(
                    'أداء المبيعات والأرباح من %s إلى %s',
                    $start->format('d/m/Y'),
                    $end->format('d/m/Y')
                ),
            ];
        } elseif ($start) {
            return [
                'title' => 'تقرير أداء العملاء',
                'description' => sprintf('أداء المبيعات والأرباح من %s حتى الآن', $start->format('d/m/Y')),
            ];
        } else {
            return [
                'title' => 'تقرير أداء العملاء',
                'description' => sprintf('أداء المبيعات والأرباح حتى %s', $end->format('d/m/Y')),
            ];
        }
    }

    /**
     * Get customers return orders performance data
     */
    public function getCustomersReturnOrdersPerformanceQuery(?string $startDate = null, ?string $endDate = null)
    {
        return Customer::query()
            ->select([
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.region',

                // Return orders data (only completed returns)
                DB::raw('COALESCE(COUNT(DISTINCT return_orders.id), 0) as return_orders_count'),
                DB::raw('COALESCE(SUM(return_orders.refund_amount), 0) as total_refund_amount'),
                DB::raw('COALESCE(AVG(return_orders.refund_amount), 0) as avg_refund_amount'),
                DB::raw('COALESCE(SUM(return_items.quantity), 0) as total_returned_quantity'),
                DB::raw('COALESCE(SUM(return_items.total), 0) as total_returned_value'),
                DB::raw('MAX(return_orders.created_at) as last_return_date'),
                DB::raw('MIN(return_orders.created_at) as first_return_date'),
            ])
            ->leftJoin('return_orders', function ($join) use ($startDate, $endDate) {
                $join->on('customers.id', '=', 'return_orders.customer_id')
                    ->where('return_orders.status', 'completed')
                    ->when($startDate, function ($query) use ($startDate) {
                        $query->where('return_orders.created_at', '>=', Carbon::parse($startDate)->startOfDay());
                    })
                    ->when($endDate, function ($query) use ($endDate) {
                        $query->where('return_orders.created_at', '<=', Carbon::parse($endDate)->endOfDay());
                    });
            })
            ->leftJoin('return_items', 'return_orders.id', '=', 'return_items.return_order_id')
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.region')
            ->havingRaw('return_orders_count > 0')
            ->orderByDesc('return_orders_count');
    }

    /**
     * Get return orders summary for customers
     */
    public function getCustomersReturnOrdersSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = DB::table('return_orders as ro')
            ->select([
                DB::raw('COUNT(DISTINCT ro.id) as total_return_orders'),
                DB::raw('COUNT(DISTINCT ro.customer_id) as customers_with_returns'),
                DB::raw('SUM(ro.refund_amount) as total_refund_amount'),
                DB::raw('AVG(ro.refund_amount) as avg_refund_amount'),
                DB::raw('SUM(ri.quantity) as total_returned_quantity'),
                DB::raw('SUM(ri.total) as total_returned_value'),
            ])
            ->leftJoin('return_items as ri', 'ro.id', '=', 'ri.return_order_id')
            ->where('ro.status', 'completed')
            ->whereNotNull('ro.customer_id')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('ro.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('ro.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            })
            ->first();

        // Get customer with most returns
        $topReturningCustomer = DB::table('return_orders as ro')
            ->select([
                'c.id',
                'c.name',
                'c.phone',
                DB::raw('COUNT(ro.id) as return_orders_count'),
                DB::raw('SUM(ro.refund_amount) as total_refund_amount'),
            ])
            ->leftJoin('customers as c', 'ro.customer_id', '=', 'c.id')
            ->where('ro.status', 'completed')
            ->whereNotNull('ro.customer_id')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('ro.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('ro.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            })
            ->groupBy('c.id', 'c.name', 'c.phone')
            ->orderByDesc('return_orders_count')
            ->limit(1)
            ->first();

        if (!$summary) {
            return [
                'total_return_orders' => 0,
                'customers_with_returns' => 0,
                'total_refund_amount' => 0,
                'avg_refund_amount' => 0,
                'total_returned_quantity' => 0,
                'total_returned_value' => 0,
                'top_returning_customer' => null,
            ];
        }

        return [
            'total_return_orders' => $summary->total_return_orders ?? 0,
            'customers_with_returns' => $summary->customers_with_returns ?? 0,
            'total_refund_amount' => $summary->total_refund_amount ?? 0,
            'avg_refund_amount' => $summary->avg_refund_amount ?? 0,
            'total_returned_quantity' => $summary->total_returned_quantity ?? 0,
            'total_returned_value' => $summary->total_returned_value ?? 0,
            'top_returning_customer' => $topReturningCustomer,
        ];
    }
}
