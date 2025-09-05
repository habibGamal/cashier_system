<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnOrder;
use App\Models\Category;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ProductsSalesReportService
{
    public function getOrdersQuery(?string $startDate = null, ?string $endDate = null)
    {
        return Order::query()
            ->where('status', OrderStatus::COMPLETED)
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            });
    }

    public function getProductsSalesPerformanceQuery(?string $startDate = null, ?string $endDate = null)
    {
        // Get products with sales data aggregated by order type
        return Product::query()
            ->whereNot('products.type', ProductType::RawMaterial)
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'products.cost',
                'products.type',
                'categories.name as category_name',
                // Total sales across all order types
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),

                // Takeaway
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "takeaway" THEN order_items.quantity ELSE 0 END), 0) as takeaway_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "takeaway" THEN order_items.total ELSE 0 END), 0) as takeaway_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "takeaway" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as takeaway_profit'),

                // Delivery
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "delivery" THEN order_items.quantity ELSE 0 END), 0) as delivery_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "delivery" THEN order_items.total ELSE 0 END), 0) as delivery_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "delivery" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as delivery_profit'),

                // Web Delivery
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_delivery" THEN order_items.quantity ELSE 0 END), 0) as web_delivery_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_delivery" THEN order_items.total ELSE 0 END), 0) as web_delivery_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_delivery" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as web_delivery_profit'),

                // Web Takeaway
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_takeaway" THEN order_items.quantity ELSE 0 END), 0) as web_takeaway_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_takeaway" THEN order_items.total ELSE 0 END), 0) as web_takeaway_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "web_takeaway" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as web_takeaway_profit'),

                // Direct Sale
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "direct_sale" THEN order_items.quantity ELSE 0 END), 0) as direct_sale_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "direct_sale" THEN order_items.total ELSE 0 END), 0) as direct_sale_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "direct_sale" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as direct_sale_profit'),
            ])
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('order_items', function ($join) use ($startDate, $endDate) {
                $join->on('products.id', '=', 'order_items.product_id')
                    ->whereExists(function ($query) use ($startDate, $endDate) {
                        $query->select(DB::raw(1))
                            ->from('orders')
                            ->whereColumn('orders.id', 'order_items.order_id')
                            ->whereBetween('orders.created_at', [
                                $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                                $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                            ]);
                    });
            })
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [
                        $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                        $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                    ]);
                ;
            })
            ->groupBy('products.id', 'products.name', 'products.price', 'products.cost', 'products.type', 'categories.name');
    }

    /**
     * Get products return orders performance data
     */
    public function getProductsReturnOrdersPerformanceQuery(?string $startDate = null, ?string $endDate = null)
    {
        return Product::query()
            ->whereNot('products.type', ProductType::RawMaterial)
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'products.cost',
                'categories.name as category_name',

                // Return orders data (only completed returns)
                DB::raw('COALESCE(COUNT(DISTINCT return_orders.id), 0) as return_orders_count'),
                DB::raw('COALESCE(SUM(return_items.quantity), 0) as total_returned_quantity'),
                DB::raw('COALESCE(SUM(return_items.total), 0) as total_returned_value'),
                DB::raw('COALESCE(SUM(return_orders.refund_amount), 0) as total_refund_amount'),
            ])
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('return_items', function ($join) use ($startDate, $endDate) {
                $join->on('products.id', '=', 'return_items.product_id')
                    ->whereExists(function ($query) use ($startDate, $endDate) {
                        $query->select(DB::raw(1))
                            ->from('return_orders')
                            ->whereColumn('return_orders.id', 'return_items.return_order_id')
                            ->where('return_orders.status', 'completed')
                            ->whereBetween('return_orders.created_at', [
                                $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                                $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                            ]);
                    });
            })
            ->leftJoin('return_orders', function ($join) use ($startDate, $endDate) {
                $join->on('return_items.return_order_id', '=', 'return_orders.id')
                    ->where('return_orders.status', 'completed')
                    ->whereBetween('return_orders.created_at', [
                        $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                        $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                    ]);
            })
            ->groupBy('products.id', 'products.name', 'products.price', 'products.cost', 'categories.name')
            ->havingRaw('total_returned_quantity > 0');
    }

    /**
     * Get period statistics summary
     */
    public function getPeriodSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = DB::table('products as p')
            ->select([
                DB::raw('COUNT(DISTINCT p.id) as total_products'),
                DB::raw('SUM(order_items.total) as total_sales'),
                DB::raw('SUM(order_items.total - (p.cost * order_items.quantity)) as total_profit'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('AVG((order_items.total - (p.cost * order_items.quantity)) / order_items.total * 100) as avg_profit_margin'),
                DB::raw('MAX(order_items.total) as best_selling_product_id'),
            ])->leftJoin('order_items', function ($join) use ($startDate, $endDate) {
                $join->on('p.id', '=', 'order_items.product_id')
                    ->whereExists(function ($query) use ($startDate, $endDate) {
                        $query->select(DB::raw(1))
                            ->from('orders')
                            ->whereColumn('orders.id', 'order_items.order_id')
                            ->whereBetween('orders.created_at', [
                                $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                                $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                            ]);
                    });
            })
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [
                        $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                        $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                    ]);
            })
            ->first();

        $mostProductQuery = DB::table('products as p')
            ->select([
                'p.id',
                'p.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_sales'),
                DB::raw('SUM(order_items.total - (p.cost * order_items.quantity)) as total_profit'),
            ])
            ->leftJoin('order_items', function ($join) use ($startDate, $endDate) {
                $join->on('p.id', '=', 'order_items.product_id')
                    ->whereExists(function ($query) use ($startDate, $endDate) {
                        $query->select(DB::raw(1))
                            ->from('orders')
                            ->whereColumn('orders.id', 'order_items.order_id')
                            ->whereBetween('orders.created_at', [
                                $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                                $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                            ]);
                    });
            })
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [
                        $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                        $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                    ]);
            })
            ->groupBy('p.id', 'p.name')
        ;

        $mostProfitableProduct = $mostProductQuery
            ->orderByDesc('total_profit')
            ->limit(1)
            ->first();

        $bestSellingProduct = $mostProductQuery
            ->orderByDesc('total_sales')
            ->limit(1)
            ->first();

        if (!$summary) {
            return [
                'total_products' => 0,
                'total_sales' => 0,
                'total_profit' => 0,
                'total_quantity' => 0,
                'avg_profit_margin' => 0,
                'best_selling_product' => null,
                'most_profitable_product' => null,
            ];
        }

        return [
            'total_products' => $summary->total_products,
            'total_sales' => $summary->total_sales,
            'total_profit' => $summary->total_profit,
            'total_quantity' => $summary->total_quantity,
            'avg_profit_margin' => $summary->avg_profit_margin,
            'best_selling_product' => $bestSellingProduct,
            'most_profitable_product' => $mostProfitableProduct,
        ];
    }

    /**
     * Get return orders summary for the period
     */
    public function getReturnOrdersSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = DB::table('return_orders as ro')
            ->select([
                DB::raw('COUNT(DISTINCT ro.id) as total_return_orders'),
                DB::raw('SUM(ro.refund_amount) as total_refund_amount'),
                DB::raw('SUM(ri.quantity) as total_returned_quantity'),
                DB::raw('SUM(ri.total) as total_returned_value'),
                DB::raw('AVG(ro.refund_amount) as avg_refund_amount'),
                DB::raw('COUNT(DISTINCT ri.product_id) as products_returned_count'),
            ])
            ->leftJoin('return_items as ri', 'ro.id', '=', 'ri.return_order_id')
            ->where('ro.status', 'completed')
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('ro.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('ro.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            })
            ->first();

        // Get most returned product
        $mostReturnedProduct = DB::table('return_items as ri')
            ->select([
                'p.id',
                'p.name',
                DB::raw('SUM(ri.quantity) as total_returned_quantity'),
                DB::raw('SUM(ri.total) as total_returned_value'),
                DB::raw('COUNT(DISTINCT ri.return_order_id) as return_orders_count'),
            ])
            ->leftJoin('products as p', 'ri.product_id', '=', 'p.id')
            ->leftJoin('return_orders as ro', function ($join) use ($startDate, $endDate) {
                $join->on('ri.return_order_id', '=', 'ro.id')
                    ->where('ro.status', 'completed')
                    ->when($startDate, function ($query) use ($startDate) {
                        $query->where('ro.created_at', '>=', Carbon::parse($startDate)->startOfDay());
                    })
                    ->when($endDate, function ($query) use ($endDate) {
                        $query->where('ro.created_at', '<=', Carbon::parse($endDate)->endOfDay());
                    });
            })
            ->whereNotNull('p.id')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_returned_quantity')
            ->limit(1)
            ->first();

        if (!$summary) {
            return [
                'total_return_orders' => 0,
                'total_refund_amount' => 0,
                'total_returned_quantity' => 0,
                'total_returned_value' => 0,
                'avg_refund_amount' => 0,
                'products_returned_count' => 0,
                'most_returned_product' => null,
            ];
        }

        return [
            'total_return_orders' => $summary->total_return_orders ?? 0,
            'total_refund_amount' => $summary->total_refund_amount ?? 0,
            'total_returned_quantity' => $summary->total_returned_quantity ?? 0,
            'total_returned_value' => $summary->total_returned_value ?? 0,
            'avg_refund_amount' => $summary->avg_refund_amount ?? 0,
            'products_returned_count' => $summary->products_returned_count ?? 0,
            'most_returned_product' => $mostReturnedProduct,
        ];
    }

    /**
     * Get order type performance summary
     */
    public function getOrderTypePerformance(?string $startDate = null, ?string $endDate = null): array
    {

        $performanceData = DB::table('orders as o')
            ->select([
                'o.type',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(oi.quantity) as total_quantity'),
                DB::raw('SUM(oi.total) as total_sales'),
                DB::raw('SUM(oi.total - (p.cost * oi.quantity)) as total_profit'),
            ])
            ->leftJoin('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'oi.product_id', '=', 'p.id')
            ->where('o.status', OrderStatus::COMPLETED)
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('o.created_at', '>=', Carbon::parse($startDate)->startOfDay());
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('o.created_at', '<=', Carbon::parse($endDate)->endOfDay());
            })
            ->groupBy('o.type')
            ->get();



        // dd($performanceData);
        return $performanceData->mapWithKeys(function ($item) {
            return [
                $item->type => [
                    'label' => OrderType::from($item->type)->label(),
                    'total_orders' => $item->total_orders,
                    'total_quantity' => $item->total_quantity,
                    'total_sales' => $item->total_sales,
                    'total_profit' => $item->total_profit,
                ],
            ];
        })->toArray();
    }

    /**
     * Get category performance summary
     */
    public function getCategoryPerformance(?string $startDate = null, ?string $endDate = null)
    {
        // Get category performance aggregated directly at the database level
        return Category::query()
            ->select([
                DB::raw('categories.id as id'),
                DB::raw('COALESCE(categories.name, "غير مصنف") as category_name'),
                DB::raw('COUNT(DISTINCT products.id) as products_count'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_items.total - (order_items.cost * order_items.quantity)), 0) as total_profit'),
            ])
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', function ($join) use ($startDate, $endDate) {
                $join->on('products.id', '=', 'order_items.product_id')
                    ->whereNot('products.type', ProductType::RawMaterial)
                    ->whereExists(function ($query) use ($startDate, $endDate) {
                        $query->select(DB::raw(1))
                            ->from('orders')
                            ->whereColumn('orders.id', 'order_items.order_id')
                            ->whereBetween('orders.created_at', [
                                $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
                                $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay()
                            ]);
                    });
            })
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
            })
            ->groupBy('categories.id', 'categories.name');
    }

    /**
     * Get period info for display
     */
    public function getPeriodInfo(?string $startDate = null, ?string $endDate = null): array
    {
        if (!$startDate && !$endDate) {
            return [
                'title' => 'تقرير أداء المنتجات - جميع الفترات',
                'description' => 'أداء المبيعات والأرباح لجميع المنتجات عبر جميع أنواع الطلبات',
            ];
        }

        $start = $startDate ? Carbon::parse($startDate) : null;
        $end = $endDate ? Carbon::parse($endDate) : null;

        if ($start && $end) {
            return [
                'title' => 'تقرير أداء المنتجات',
                'description' => sprintf(
                    'أداء المبيعات والأرباح من %s إلى %s',
                    $start->format('d/m/Y'),
                    $end->format('d/m/Y')
                ),
            ];
        } elseif ($start) {
            return [
                'title' => 'تقرير أداء المنتجات',
                'description' => sprintf('أداء المبيعات والأرباح من %s حتى الآن', $start->format('d/m/Y')),
            ];
        } else {
            return [
                'title' => 'تقرير أداء المنتجات',
                'description' => sprintf('أداء المبيعات والأرباح حتى %s', $end->format('d/m/Y')),
            ];
        }
    }
}
