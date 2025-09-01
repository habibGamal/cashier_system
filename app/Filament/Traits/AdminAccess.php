<?php

namespace App\Filament\Traits;

trait AdminAccess
{
    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin();
    }
}
