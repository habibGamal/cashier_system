<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class NoActiveShiftWidget extends Widget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.no-active-shift';

    protected int|string|array $columnSpan = 'full';
}
