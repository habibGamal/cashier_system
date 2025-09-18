<?php

namespace App\Filament\Widgets;

use App\Services\MinimumStockReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MinimumStockStats extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected MinimumStockReportService $minimumStockReportService;

    public function boot(): void
    {
        $this->minimumStockReportService = app(MinimumStockReportService::class);
    }

    public function getHeading(): string
    {
        return 'إحصائيات المخزون المنخفض';
    }

    protected function getStats(): array
    {
        $stats = $this->minimumStockReportService->getMinimumStockStats();

        return [
            Stat::make('إجمالي المنتجات', number_format($stats['totalProducts']))
                ->description('إجمالي المنتجات في النظام')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('منتجات تحت الحد الأدنى', number_format($stats['belowMinStockCount']))
                ->description(sprintf('%.1f%% من إجمالي المنتجات', $stats['belowMinStockPercentage']))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('منتجات بمخزون صفر', number_format($stats['zeroStockCount']))
                ->description('منتجات نفدت من المخزون')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('منتجات بمخزون حرج', number_format($stats['criticalStockCount']))
                ->description('أقل من نصف الحد الأدنى')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('danger'),
        ];
    }
}
