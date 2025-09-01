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
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: (float) $data['price'],
            cost: (float) $data['cost'],
            notes: $data['notes'] ?? null,
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
        ];
    }

    public function getTotal(): float
    {
        return $this->price * $this->quantity;
    }

    public function getTotalCost(): float
    {
        return $this->cost * $this->quantity;
    }
}
