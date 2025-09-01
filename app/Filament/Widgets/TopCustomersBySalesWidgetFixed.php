<?php

// namespace App\Filament\Widgets;

// use App\Services\CustomersPerformanceReportService;
// use Filament\Widgets\ChartWidget;
// use Filament\Widgets\Concerns\InteractsWithPageFilters;

// class TopCustomersBySalesWidget extends ChartWidget
// {
//     use InteractsWithPageFilters;

//     protected static ?string $heading = 'أفضل 10 عملاء بالمبيعات';

//     protected int|string|array $columnSpan = 'lg:col-span-2';

//     protected static ?string $maxHeight = '300px';

//     protected CustomersPerformanceReportService $customersReportService;

//     public function boot(): void
//     {
//         $this->customersReportService = app(CustomersPerformanceReportService::class);
//     }

//     protected function getData(): array
//     {
//         $startDate = $this->filters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
//         $endDate = $this->filters['endDate'] ?? now()->endOfDay()->toDateString();

//         $topCustomers = $this->customersReportService->getCustomersPerformanceQuery($startDate, $endDate)
//             ->orderByDesc('total_sales')
//             ->limit(10)
//             ->get();

//         return [
//             'datasets' => [
//                 [
//                     'label' => 'المبيعات (ج.م)',
//                     'data' => $topCustomers->pluck('total_sales')->toArray(),
//                     'backgroundColor' => [
//                         'rgba(54, 162, 235, 0.2)',
//                         'rgba(255, 99, 132, 0.2)',
//                         'rgba(255, 205, 86, 0.2)',
//                         'rgba(75, 192, 192, 0.2)',
//                         'rgba(153, 102, 255, 0.2)',
//                         'rgba(255, 159, 64, 0.2)',
//                         'rgba(199, 199, 199, 0.2)',
//                         'rgba(83, 102, 255, 0.2)',
//                         'rgba(255, 99, 255, 0.2)',
//                         'rgba(99, 255, 132, 0.2)',
//                     ],
//                     'borderColor' => [
//                         'rgba(54, 162, 235, 1)',
//                         'rgba(255, 99, 132, 1)',
//                         'rgba(255, 205, 86, 1)',
//                         'rgba(75, 192, 192, 1)',
//                         'rgba(153, 102, 255, 1)',
//                         'rgba(255, 159, 64, 1)',
//                         'rgba(199, 199, 199, 1)',
//                         'rgba(83, 102, 255, 1)',
//                         'rgba(255, 99, 255, 1)',
//                         'rgba(99, 255, 132, 1)',
//                     ],
//                     'borderWidth' => 1,
//                 ],
//             ],
//             'labels' => $topCustomers->map(function ($customer) {
//                 return mb_strlen($customer->name, 'UTF-8') > 15
//                     ? mb_substr($customer->name, 0, 15, 'UTF-8') . '...'
//                     : $customer->name;
//             })->toArray(),
//         ];
//     }

//     protected function getType(): string
//     {
//         return 'bar';
//     }

//     protected function getOptions(): array
//     {
//         return [
//             'plugins' => [
//                 'legend' => [
//                     'display' => false,
//                 ],
//             ],
//             'scales' => [
//                 'y' => [
//                     'beginAtZero' => true,
//                     'ticks' => [
//                         'callback' => new \Filament\Support\RawJs('function(value) { return value.toLocaleString() + " ج.م"; }'),
//                     ],
//                 ],
//             ],
//             'responsive' => true,
//             'maintainAspectRatio' => false,
//         ];
//     }
// }
