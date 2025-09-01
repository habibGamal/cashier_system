<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class CustomerActivityTrendWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ?string $heading = 'اتجاه نشاط العملاء الجدد مقابل المعادين';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        // Get data grouped by customer type (new vs returning)
        $newCustomersQuery = $this->customersReportService->getOrdersQuery($startDate, $endDate)
            ->selectRaw('DATE(orders.created_at) as order_date')
            ->selectRaw('COUNT(DISTINCT CASE WHEN first_order.first_order_date = DATE(orders.created_at) THEN orders.customer_id END) as new_customers')
            ->selectRaw('COUNT(DISTINCT CASE WHEN first_order.first_order_date < DATE(orders.created_at) THEN orders.customer_id END) as returning_customers')
            ->leftJoin(DB::raw('(
                SELECT customer_id, MIN(DATE(created_at)) as first_order_date
                FROM orders
                WHERE status = "completed"
                GROUP BY customer_id
            ) as first_order'), 'orders.customer_id', '=', 'first_order.customer_id')
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $labels = [];
        $newCustomers = [];
        $returningCustomers = [];

        foreach ($newCustomersQuery as $data) {
            $labels[] = Carbon::parse($data->order_date)->format('m/d');
            $newCustomers[] = $data->new_customers;
            $returningCustomers[] = $data->returning_customers;
        }

        return [
            'datasets' => [
                [
                    'label' => 'عملاء جدد',
                    'data' => $newCustomers,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
                [
                    'label' => 'عملاء معاودون',
                    'data' => $returningCustomers,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'عدد العملاء',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'التاريخ',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
