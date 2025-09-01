<?php

use App\DTOs\Orders\OrderItemDTO;

describe('OrderItemDTO', function () {
    it('can be created with valid data', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 2,
            price: 25.50,
            cost: 15.00,
            notes: 'Extra cheese'
        );

        expect($dto->productId)->toBe(1)
            ->and($dto->quantity)->toBe(2)
            ->and($dto->price)->toBe(25.50)
            ->and($dto->cost)->toBe(15.00)
            ->and($dto->notes)->toBe('Extra cheese');
    });

    it('can be created from array', function () {
        $data = [
            'product_id' => 1,
            'quantity' => 3,
            'price' => 20.00,
            'cost' => 12.00,
            'notes' => 'No onions',
        ];

        $dto = OrderItemDTO::fromArray($data);

        expect($dto->productId)->toBe(1)
            ->and($dto->quantity)->toBe(3)
            ->and($dto->price)->toBe(20.00)
            ->and($dto->cost)->toBe(12.00)
            ->and($dto->notes)->toBe('No onions');
    });

    it('can be converted to array', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 2,
            price: 25.50,
            cost: 15.00
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'product_id' => 1,
            'quantity' => 2,
            'price' => 25.50,
            'cost' => 15.00,
            'total' => 51.00,
            'notes' => null,
        ]);
    });

    it('calculates total correctly', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 3,
            price: 15.75,
            cost: 10.00
        );

        expect($dto->getTotal())->toBe(47.25);
    });

    it('calculates total cost correctly', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 4,
            price: 20.00,
            cost: 12.50
        );

        expect($dto->getTotalCost())->toBe(50.00);
    });

    it('throws exception when quantity is zero', function () {
        expect(fn() => new OrderItemDTO(
            productId: 1,
            quantity: 0,
            price: 10.00,
            cost: 5.00
        ))->toThrow(InvalidArgumentException::class, 'Quantity must be greater than zero');
    });

    it('throws exception when quantity is negative', function () {
        expect(fn() => new OrderItemDTO(
            productId: 1,
            quantity: -1,
            price: 10.00,
            cost: 5.00
        ))->toThrow(InvalidArgumentException::class, 'Quantity must be greater than zero');
    });

    it('throws exception when price is negative', function () {
        expect(fn() => new OrderItemDTO(
            productId: 1,
            quantity: 1,
            price: -10.00,
            cost: 5.00
        ))->toThrow(InvalidArgumentException::class, 'Price cannot be negative');
    });

    it('throws exception when cost is negative', function () {
        expect(fn() => new OrderItemDTO(
            productId: 1,
            quantity: 1,
            price: 10.00,
            cost: -5.00
        ))->toThrow(InvalidArgumentException::class, 'Cost cannot be negative');
    });

    it('allows zero price', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 1,
            price: 0.00,
            cost: 5.00
        );

        expect($dto->price)->toBe(0.00)
            ->and($dto->getTotal())->toBe(0.00);
    });

    it('allows zero cost', function () {
        $dto = new OrderItemDTO(
            productId: 1,
            quantity: 1,
            price: 10.00,
            cost: 0.00
        );

        expect($dto->cost)->toBe(0.00)
            ->and($dto->getTotalCost())->toBe(0.00);
    });
});
