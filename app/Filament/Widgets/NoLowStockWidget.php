<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class NoLowStockWidget extends Widget
{
    protected string $view = 'filament.widgets.no-low-stock-widget';

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'لا توجد منتجات تحت الحد الأدنى';
    }
}
