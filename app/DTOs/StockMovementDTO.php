<?php

namespace App\DTOs;

use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use App\Models\InventoryItem;

readonly class StockMovementDTO
{
    public function __construct(
        public int $productId,
        public float $quantity,
        public InventoryMovementOperation $operation,
        public MovementReason $reason,
        public mixed $referenceable = null,
        public ?InventoryItem $existingInventoryItem = null,
    ) {}

    public function isNewInventoryItem(): bool
    {
        return $this->existingInventoryItem === null;
    }

    public function calculateNewQuantity(): float
    {
        if ($this->existingInventoryItem === null) {
            return $this->quantity;
        }

        return $this->operation->isIncoming()
            ? $this->existingInventoryItem->quantity + $this->quantity
            : $this->existingInventoryItem->quantity - $this->quantity;
    }
}
