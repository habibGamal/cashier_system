<?php

namespace App\DTOs\Orders;

use InvalidArgumentException;
use App\Enums\OrderType;

class CreateOrderDTO
{
    public function __construct(
        public readonly OrderType $type,
        public readonly int $shiftId,
        public readonly int $userId,
        public readonly ?string $tableNumber = null,
        public readonly ?int $customerId = null,
        public readonly ?int $driverId = null,
        public readonly ?string $kitchenNotes = null,
        public readonly ?string $orderNotes = null,
    ) {
        if ($type->requiresTable() && !$tableNumber) {
            throw new InvalidArgumentException('Table number is required for dine-in orders');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: OrderType::from($data['type']),
            shiftId: $data['shift_id'],
            userId: $data['user_id'],
            tableNumber: $data['table_number'] ?? null,
            customerId: $data['customer_id'] ?? null,
            driverId: $data['driver_id'] ?? null,
            kitchenNotes: $data['kitchen_notes'] ?? null,
            orderNotes: $data['order_notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'shift_id' => $this->shiftId,
            'user_id' => $this->userId,
            'table_number' => $this->tableNumber,
            'customer_id' => $this->customerId,
            'driver_id' => $this->driverId,
            'kitchen_notes' => $this->kitchenNotes,
            'order_notes' => $this->orderNotes,
        ];
    }
}
