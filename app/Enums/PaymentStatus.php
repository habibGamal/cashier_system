<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case PARTIAL_PAID = 'partial_paid';
    case FULL_PAID = 'full_paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'في الانتظار',
            self::PARTIAL_PAID => 'مدفوع جزئياً',
            self::FULL_PAID => 'مدفوع بالكامل',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PARTIAL_PAID => 'warning',
            self::FULL_PAID => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PARTIAL_PAID => 'heroicon-o-banknotes',
            self::FULL_PAID => 'heroicon-o-check-circle',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function isFullyPaid(): bool
    {
        return $this === self::FULL_PAID;
    }

    public function requiresPayment(): bool
    {
        return $this !== self::FULL_PAID;
    }
}
