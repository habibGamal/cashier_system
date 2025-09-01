<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\Enums\IconPosition;

class CustomerLoyaltyInsightsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected CustomersPerformanceReportService $customersReportService;

    // protected static ?string $heading = 'رؤى ولاء العملاء';

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getStats(): array
    {
        $insights = $this->getCustomerLoyaltyInsights();

        return [
            Stat::make('معدل العودة', number_format($insights['return_rate'], 1) . '%')
                ->description('نسبة العملاء الذين قاموا بطلب أكثر من مرة')
                ->descriptionIcon('heroicon-m-arrow-path', IconPosition::Before)
                ->color($insights['return_rate'] >= 50 ? 'success' : ($insights['return_rate'] >= 30 ? 'warning' : 'danger')),

            Stat::make('متوسط مدة العلاقة', $insights['avg_customer_lifetime'] . ' يوم')
                ->description('متوسط المدة بين أول وآخر طلب للعميل')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color('info'),

            Stat::make('العملاء النشطون', $insights['active_customers'])
                ->description('عملاء قاموا بطلب خلال آخر 30 يوم')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->color('success'),

            Stat::make('العملاء المعرضون للفقدان', $insights['at_risk_customers'])
                ->description('عملاء لم يطلبوا خلال آخر 60 يوم')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('warning'),

            Stat::make('العملاء عالي القيمة', $insights['high_value_customers'])
                ->description('عملاء بقيمة طلبات أعلى من المتوسط')
                ->descriptionIcon('heroicon-m-star', IconPosition::Before)
                ->color('success'),

            Stat::make('متوسط الفترة بين الطلبات', $insights['avg_days_between_orders'] . ' يوم')
                ->description('متوسط عدد الأيام بين الطلبات للعملاء المعاودين')
                ->descriptionIcon('heroicon-m-calendar-days', IconPosition::Before)
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    private function getCustomerLoyaltyInsights(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $customers = $this->customersReportService->getCustomersPerformanceQuery($startDate, $endDate)->get();

        $totalCustomers = $customers->count();
        $returningCustomers = $customers->where('total_orders', '>', 1)->count();
        $returnRate = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;

        // Calculate average customer lifetime
        $lifetimes = $customers->map(function ($customer) {
            if ($customer->first_order_date && $customer->last_order_date) {
                return Carbon::parse($customer->first_order_date)
                    ->diffInDays(Carbon::parse($customer->last_order_date));
            }
            return 0;
        })->filter(fn($lifetime) => $lifetime > 0);

        $avgLifetime = $lifetimes->count() > 0 ? $lifetimes->avg() : 0;

        // Active customers (ordered in last 30 days)
        $activeCustomers = $customers->where('last_order_date', '>=', now()->subDays(30)->toDateString())->count();

        // At risk customers (haven't ordered in last 60 days but have ordered before)
        $atRiskCustomers = $customers->filter(function ($customer) {
            return $customer->last_order_date &&
                   Carbon::parse($customer->last_order_date)->lt(now()->subDays(60)) &&
                   $customer->total_orders > 0;
        })->count();

        // High value customers (above average order value)
        $avgOrderValue = $customers->where('total_orders', '>', 0)->avg('avg_order_value') ?? 0;
        $highValueCustomers = $customers->where('avg_order_value', '>', $avgOrderValue)->count();

        // Average days between orders for returning customers
        $avgDaysBetweenOrders = 0;
        $returningCustomersWithLifetime = $customers->filter(function ($customer) {
            return $customer->total_orders > 1 &&
                   $customer->first_order_date &&
                   $customer->last_order_date;
        });

        if ($returningCustomersWithLifetime->count() > 0) {
            $avgDaysBetweenOrders = $returningCustomersWithLifetime->map(function ($customer) {
                $lifetime = Carbon::parse($customer->first_order_date)
                    ->diffInDays(Carbon::parse($customer->last_order_date));
                return $customer->total_orders > 1 ? $lifetime / ($customer->total_orders - 1) : 0;
            })->avg();
        }

        return [
            'return_rate' => $returnRate,
            'avg_customer_lifetime' => round($avgLifetime),
            'active_customers' => $activeCustomers,
            'at_risk_customers' => $atRiskCustomers,
            'high_value_customers' => $highValueCustomers,
            'avg_days_between_orders' => round($avgDaysBetweenOrders),
        ];
    }
}
