<?php

namespace App\Filament\Traits;

trait ViewerAccess
{
    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isViewer();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isViewer();
    }
}
