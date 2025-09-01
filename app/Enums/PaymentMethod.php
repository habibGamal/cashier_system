<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case CASH = 'cash';
    case CARD = 'card';
    case TALABAT_CARD = 'talabat_card';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => 'نقدي',
            self::CARD => 'بطاقة',
            self::TALABAT_CARD => 'بطاقة طلبات',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::CASH => 'success',
            self::CARD => 'info',
            self::TALABAT_CARD => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CASH => 'heroicon-o-banknotes',
            self::CARD => 'heroicon-o-credit-card',
            self::TALABAT_CARD => 'heroicon-o-device-phone-mobile',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function affectsCashBalance(): bool
    {
        return $this === self::CASH;
    }
}
