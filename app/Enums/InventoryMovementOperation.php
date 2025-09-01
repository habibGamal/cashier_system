<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InventoryMovementOperation: string implements HasColor, HasIcon, HasLabel
{
    case IN = 'in';
    case OUT = 'out';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'دخول',
            self::OUT => 'خروج',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::IN => 'success',
            self::OUT => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::IN => 'heroicon-o-arrow-down-circle',
            self::OUT => 'heroicon-o-arrow-up-circle',
        };
    }

    public function isIncoming(): bool
    {
        return $this === self::IN;
    }

    public function isOutgoing(): bool
    {
        return $this === self::OUT;
    }
}
