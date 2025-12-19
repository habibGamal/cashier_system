<?php

namespace App\DTOs\Orders;

use InvalidArgumentException;

class OrderItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly float $quantity,
        public readonly float $price,
        public readonly float $cost,
        public readonly ?string $notes = null,
        public readonly float $itemDiscount = 0,
        public readonly ?string $itemDiscountType = null,
        public readonly ?float $itemDiscountPercent = null,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero');
        }

        if ($price < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        if ($cost < 0) {
            throw new InvalidArgumentException('Cost cannot be negative');
        }

        if ($itemDiscount < 0) {
            throw new InvalidArgumentException('Item discount cannot be negative');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: (float) $data['price'],
            cost: (float) $data['cost'],
            notes: $data['notes'] ?? null,
            itemDiscount: (float) ($data['item_discount'] ?? 0),
            itemDiscountType: $data['item_discount_type'] ?? null,
            itemDiscountPercent: isset($data['item_discount_percent']) ? (float) $data['item_discount_percent'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'cost' => $this->cost,
            'total' => $this->getTotal(),
            'notes' => $this->notes,
            'item_discount' => $this->getCalculatedDiscount(),
            'item_discount_type' => $this->itemDiscountType,
            'item_discount_percent' => $this->itemDiscountPercent,
        ];
    }

    public function getTotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get the calculated discount amount based on type
     */
    public function getCalculatedDiscount(): float
    {
        $subtotal = $this->getTotal();

        if ($this->itemDiscountType === 'percent' && $this->itemDiscountPercent !== null) {
            $discount = $subtotal * ($this->itemDiscountPercent / 100);
            return min($discount, $subtotal);
        }

        return min($this->itemDiscount, $subtotal);
    }

    /**
     * Get the total after discount
     */
    public function getDiscountedTotal(): float
    {
        return $this->getTotal() - $this->getCalculatedDiscount();
    }

    public function getTotalCost(): float
    {
        return $this->cost * $this->quantity;
    }
}
