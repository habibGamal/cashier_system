<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Casts\AsStringable;

enum ProductType: string implements HasColor, HasIcon, HasLabel
{

    case Manufactured = 'manufactured';
    case RawMaterial = 'raw_material';
    case Consumable = 'consumable';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Manufactured => 'primary',
            self::RawMaterial => 'info',
            self::Consumable => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Manufactured => 'heroicon-o-cog-6-tooth',
            self::RawMaterial => 'heroicon-o-cube',
            self::Consumable => 'heroicon-o-shopping-bag',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Manufactured => 'منتج مُصنع',
            self::RawMaterial => 'مادة خام',
            self::Consumable => 'منتج استهلاكي',
        };
    }

    public static function toSelectArray(): array
    {
        return [
            self::Manufactured->value => self::Manufactured->getLabel(),
            self::RawMaterial->value => self::RawMaterial->getLabel(),
            self::Consumable->value => self::Consumable->getLabel(),
        ];
    }
}
