<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MovementReason: string implements HasColor, HasIcon, HasLabel
{
    case PURCHASE = 'purchase';
    case PURCHASE_RETURN = 'purchase_return';
    case ORDER = 'order';
    case ORDER_RETURN = 'order_return';
    case WASTE = 'waste';
    case STOCKTAKING = 'stocktaking';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER = 'transfer';
    case INITIAL_STOCK = 'initial_stock';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'مشتريات',
            self::PURCHASE_RETURN => 'مرتجع مشتريات',
            self::ORDER => 'طلب',
            self::ORDER_RETURN => 'مرتجع طلب',
            self::WASTE => 'تالف',
            self::STOCKTAKING => 'جرد',
            self::ADJUSTMENT => 'تعديل',
            self::TRANSFER => 'نقل',
            self::INITIAL_STOCK => 'رصيد ابتدائي',
            self::MANUAL => 'يدوي',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PURCHASE => 'success',
            self::PURCHASE_RETURN => 'danger',
            self::ORDER => 'danger',
            self::ORDER_RETURN => 'success',
            self::WASTE => 'gray',
            self::STOCKTAKING => 'info',
            self::ADJUSTMENT => 'warning',
            self::TRANSFER => 'primary',
            self::INITIAL_STOCK => 'success',
            self::MANUAL => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PURCHASE => 'heroicon-o-shopping-cart',
            self::PURCHASE_RETURN => 'heroicon-o-arrow-uturn-left',
            self::ORDER => 'heroicon-o-receipt-refund',
            self::ORDER_RETURN => 'heroicon-o-arrow-uturn-right',
            self::WASTE => 'heroicon-o-trash',
            self::STOCKTAKING => 'heroicon-o-document-magnifying-glass',
            self::ADJUSTMENT => 'heroicon-o-adjustments-horizontal',
            self::TRANSFER => 'heroicon-o-arrow-right-arrow-left',
            self::INITIAL_STOCK => 'heroicon-o-sparkles',
            self::MANUAL => 'heroicon-o-pencil',
        };
    }

    public function isIncoming(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::ORDER_RETURN,
            self::INITIAL_STOCK,
            self::ADJUSTMENT, // Can be both, but typically used for increases
        ]);
    }

    public function isOutgoing(): bool
    {
        return in_array($this, [
            self::PURCHASE_RETURN,
            self::ORDER,
            self::WASTE,
            self::TRANSFER,
        ]);
    }

    public function canBeManual(): bool
    {
        return in_array($this, [
            self::ADJUSTMENT,
            self::INITIAL_STOCK,
            self::MANUAL,
            self::WASTE,
        ]);
    }
}
