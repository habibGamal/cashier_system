<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case ADMIN = 'admin';
    case VIEWER = 'viewer';
    case CASHIER = 'cashier';
    case WATCHER = 'watcher';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'مدير',
            self::VIEWER => 'متابع تقارير',
            self::CASHIER => 'كاشير',
            self::WATCHER => 'مراقب',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'مدير',
            self::VIEWER => 'متابع تقارير',
            self::CASHIER => 'كاشير',
            self::WATCHER => 'مراقب',
        };
    }

    public function canManageOrders(): bool
    {
        return in_array($this, [self::ADMIN, self::CASHIER]);
    }

    public function canCancelOrders(): bool
    {
        return $this === self::ADMIN;
    }

    public function canApplyDiscounts(): bool
    {
        return $this === self::ADMIN;
    }

    public function canAccessReports(): bool
    {
        return in_array($this, haystack: [self::ADMIN, self::VIEWER, self::WATCHER]);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ADMIN => 'text-red-500',
            self::VIEWER => 'text-blue-500',
            self::CASHIER => 'text-green-500',
            self::WATCHER => 'text-yellow-500',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ADMIN => 'heroicon-s-shield-check',
            self::VIEWER => 'heroicon-s-eye',
            self::CASHIER => 'heroicon-s-cash',
            self::WATCHER => 'heroicon-s-eye-off',
        };
    }
}
