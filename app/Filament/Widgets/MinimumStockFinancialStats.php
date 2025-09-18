<?php

namespace App\Filament\Widgets;

use App\Services\MinimumStockReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MinimumStockFinancialStats extends BaseWidget
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
        return 'التكلفة المالية للمخزون المنخفض';
    }

    protected function getStats(): array
    {
        $recommendations = $this->minimumStockReportService->getPurchaseRecommendations();


        return [
            Stat::make('التكلفة المقدرة للتجديد', number_format($recommendations['totalValueBelowMinStock'], 2).' جنيه')
                ->description('تكلفة شراء المنتجات المطلوبة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),


            Stat::make('أولوية التجديد', $recommendations['zeroStockCount'] > 0 ? 'عاجل' : 'متوسط')
                ->description($recommendations['zeroStockCount'].' منتج نفد تماماً')
                ->descriptionIcon('heroicon-m-clock')
                ->color($recommendations['zeroStockCount'] > 0 ? 'danger' : 'warning'),
        ];
    }
}
