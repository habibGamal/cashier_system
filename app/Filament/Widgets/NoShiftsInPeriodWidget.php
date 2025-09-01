<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class NoShiftsInPeriodWidget extends Widget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.no-shifts-in-period';

    protected int|string|array $columnSpan = 'full';
}
