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
        public readonly ?int $customerId = null,
        public readonly ?int $driverId = null,
        public readonly ?string $orderNotes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: OrderType::from($data['type']),
            shiftId: $data['shift_id'],
            userId: $data['user_id'],
            customerId: $data['customer_id'] ?? null,
            driverId: $data['driver_id'] ?? null,
            orderNotes: $data['order_notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'shift_id' => $this->shiftId,
            'user_id' => $this->userId,
            'customer_id' => $this->customerId,
            'driver_id' => $this->driverId,
            'order_notes' => $this->orderNotes,
        ];
    }
}
