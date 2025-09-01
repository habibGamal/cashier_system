<?php

namespace App\Filament\Widgets;

use App\Services\CustomersPerformanceReportService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CustomerSegmentsWidget extends ChartWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    use InteractsWithPageFilters;

    protected ?string $heading = 'تقسيم العملاء حسب الأداء';

    protected int|string|array $columnSpan = 'full';

    // protected static ?string $maxHeight = '300px';

    protected CustomersPerformanceReportService $customersReportService;

    public function boot(): void
    {
        $this->customersReportService = app(CustomersPerformanceReportService::class);
    }

    protected function getData(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(30)->startOfDay()->toDateString();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfDay()->toDateString();

        $segments = $this->customersReportService->getCustomerSegments($startDate, $endDate);

        $labels = [];
        $counts = [];
        $sales = [];

        foreach ($segments as $segmentKey => $segmentData) {
            $segmentLabels = [
                'vip' => 'عملاء VIP',
                'loyal' => 'عملاء مخلصين',
                'regular' => 'عملاء عاديين',
                'new' => 'عملاء جدد',
            ];

            $labels[] = $segmentLabels[$segmentKey] ?? $segmentKey;
            $counts[] = $segmentData['count'];
            $sales[] = $segmentData['total_sales'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد العملاء',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(255, 193, 7, 0.8)',   // VIP - Gold
                        'rgba(40, 167, 69, 0.8)',   // Loyal - Green
                        'rgba(23, 162, 184, 0.8)',  // Regular - Blue
                        'rgba(108, 117, 125, 0.8)', // New - Gray
                    ],
                    'borderColor' => [
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(108, 117, 125, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    public function getDescription(): ?string
    {
        return 'توزيع العملاء حسب مستوى نشاطهم: VIP (5000+ ج.م، 20+ طلب)، مخلصين (2000+ ج.م، 10+ طلب)، عاديين (5+ طلبات)، جدد (أقل من 5 طلبات)';
    }
}
