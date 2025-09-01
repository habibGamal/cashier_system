<?php

use App\DTOs\Orders\CreateOrderDTO;
use App\Enums\OrderType;

describe('CreateOrderDTO', function () {
    it('can be created with valid data', function () {
        $dto = new CreateOrderDTO(
            type: OrderType::DINE_IN,
            shiftId: 1,
            userId: 1,
            tableNumber: 'T001'
        );

        expect($dto->type)->toBe(OrderType::DINE_IN)
            ->and($dto->shiftId)->toBe(1)
            ->and($dto->userId)->toBe(1)
            ->and($dto->tableNumber)->toBe('T001');
    });

    it('can be created from array', function () {
        $data = [
            'type' => 'dine_in',
            'shift_id' => 1,
            'user_id' => 1,
            'table_number' => 'T001',
            'customer_id' => 2,
            'kitchen_notes' => 'No spicy',
        ];

        $dto = CreateOrderDTO::fromArray($data);

        expect($dto->type)->toBe(OrderType::DINE_IN)
            ->and($dto->shiftId)->toBe(1)
            ->and($dto->userId)->toBe(1)
            ->and($dto->tableNumber)->toBe('T001')
            ->and($dto->customerId)->toBe(2)
            ->and($dto->kitchenNotes)->toBe('No spicy');
    });

    it('can be converted to array', function () {
        $dto = new CreateOrderDTO(
            type: OrderType::TAKEAWAY,
            shiftId: 1,
            userId: 1,
            customerId: 2
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'type' => 'takeaway',
            'shift_id' => 1,
            'user_id' => 1,
            'table_number' => null,
            'customer_id' => 2,
            'driver_id' => null,
            'kitchen_notes' => null,
            'order_notes' => null,
        ]);
    });

    it('throws exception when table number is required but not provided', function () {
        expect(fn() => new CreateOrderDTO(
            type: OrderType::DINE_IN,
            shiftId: 1,
            userId: 1
        ))->toThrow(InvalidArgumentException::class, 'Table number is required for dine-in orders');
    });

    it('allows null table number for takeaway orders', function () {
        $dto = new CreateOrderDTO(
            type: OrderType::TAKEAWAY,
            shiftId: 1,
            userId: 1
        );

        expect($dto->tableNumber)->toBeNull();
    });

    it('allows null table number for delivery orders', function () {
        $dto = new CreateOrderDTO(
            type: OrderType::DELIVERY,
            shiftId: 1,
            userId: 1,
            driverId: 1
        );

        expect($dto->tableNumber)->toBeNull()
            ->and($dto->driverId)->toBe(1);
    });
});
