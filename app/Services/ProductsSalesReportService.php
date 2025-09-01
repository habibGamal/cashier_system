<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
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

                // Dine In
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "dine_in" THEN order_items.quantity ELSE 0 END), 0) as dine_in_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "dine_in" THEN order_items.total ELSE 0 END), 0) as dine_in_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "dine_in" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as dine_in_profit'),

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

                // Talabat
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "talabat" THEN order_items.quantity ELSE 0 END), 0) as talabat_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "talabat" THEN order_items.total ELSE 0 END), 0) as talabat_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "talabat" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as talabat_profit'),

                // Companies
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "companies" THEN order_items.quantity ELSE 0 END), 0) as companies_quantity'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "companies" THEN order_items.total ELSE 0 END), 0) as companies_sales'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.type = "companies" THEN order_items.total - (order_items.cost * order_items.quantity) ELSE 0 END), 0) as companies_profit'),
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
