<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderType: string implements HasColor, HasIcon, HasLabel
{
    case TAKEAWAY = 'takeaway';
    case DELIVERY = 'delivery';
    case WEB_DELIVERY = 'web_delivery';
    case WEB_TAKEAWAY = 'web_takeaway';
    case DIRECT_SALE = 'direct_sale';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TAKEAWAY => 'تيك أواي',
            self::DELIVERY => 'دليفري',
            self::WEB_DELIVERY => 'اونلاين دليفري',
            self::WEB_TAKEAWAY => 'اونلاين تيك أواي',
            self::DIRECT_SALE => 'بيع مباشر',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::TAKEAWAY => 'info',
            self::DELIVERY => 'danger',
            self::WEB_DELIVERY => 'primary',
            self::WEB_TAKEAWAY => 'primary',
            self::DIRECT_SALE => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::TAKEAWAY => 'heroicon-o-shopping-bag',
            self::DELIVERY => 'heroicon-o-truck',
            self::WEB_DELIVERY => 'heroicon-o-globe-alt',
            self::WEB_TAKEAWAY => 'heroicon-o-computer-desktop',
            self::DIRECT_SALE => 'heroicon-o-banknotes',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
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
