<?php

namespace App\DTOs\Orders;

use InvalidArgumentException;
use App\Enums\PaymentMethod;

class PaymentDTO
{
    public function __construct(
        public readonly float $amount,
        public readonly PaymentMethod $method,
        public readonly int $orderId,
        public readonly int $shiftId,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            method: PaymentMethod::from($data['method']),
            orderId: $data['order_id'],
            shiftId: $data['shift_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'method' => $this->method->value,
            'order_id' => $this->orderId,
            'shift_id' => $this->shiftId,
        ];
    }
}
