<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderType: string implements HasColor, HasIcon, HasLabel
{
    case DINE_IN = 'dine_in';
    case TAKEAWAY = 'takeaway';
    case DELIVERY = 'delivery';
    case COMPANIES = 'companies';
    case TALABAT = 'talabat';
    case WEB_DELIVERY = 'web_delivery';
    case WEB_TAKEAWAY = 'web_takeaway';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DINE_IN => 'صالة',
            self::TAKEAWAY => 'تيك أواي',
            self::DELIVERY => 'دليفري',
            self::COMPANIES => 'شركات',
            self::TALABAT => 'طلبات',
            self::WEB_DELIVERY => 'اونلاين دليفري',
            self::WEB_TAKEAWAY => 'اونلاين تيك أواي',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::DINE_IN => 'success',
            self::TAKEAWAY => 'info',
            self::DELIVERY => 'danger',
            self::COMPANIES => 'gray',
            self::TALABAT => 'warning',
            self::WEB_DELIVERY => 'primary',
            self::WEB_TAKEAWAY => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DINE_IN => 'heroicon-o-home',
            self::TAKEAWAY => 'heroicon-o-shopping-bag',
            self::DELIVERY => 'heroicon-o-truck',
            self::COMPANIES => 'heroicon-o-building-office',
            self::TALABAT => 'heroicon-o-device-phone-mobile',
            self::WEB_DELIVERY => 'heroicon-o-globe-alt',
            self::WEB_TAKEAWAY => 'heroicon-o-computer-desktop',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function requiresTable(): bool
    {
        return $this === self::DINE_IN;
    }

    public function hasDeliveryFee(): bool
    {
        return in_array($this, [self::DELIVERY, self::WEB_DELIVERY]);
    }

    public function isWebOrder(): bool
    {
        return in_array($this, [self::WEB_DELIVERY, self::WEB_TAKEAWAY]);
    }
}
