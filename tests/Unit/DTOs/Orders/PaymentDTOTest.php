<?php

use App\DTOs\Orders\PaymentDTO;
use App\Enums\PaymentMethod;

describe('PaymentDTO', function () {
    it('can be created with valid data', function () {
        $dto = new PaymentDTO(
            amount: 100.50,
            method: PaymentMethod::CASH,
            orderId: 1,
            shiftId: 1
        );

        expect($dto->amount)->toBe(100.50)
            ->and($dto->method)->toBe(PaymentMethod::CASH)
            ->and($dto->orderId)->toBe(1)
            ->and($dto->shiftId)->toBe(1);
    });

    it('can be created from array', function () {
        $data = [
            'amount' => 75.25,
            'method' => 'card',
            'order_id' => 2,
            'shift_id' => 1,
        ];

        $dto = PaymentDTO::fromArray($data);

        expect($dto->amount)->toBe(75.25)
            ->and($dto->method)->toBe(PaymentMethod::CARD)
            ->and($dto->orderId)->toBe(2)
            ->and($dto->shiftId)->toBe(1);
    });

    it('can be converted to array', function () {
        $dto = new PaymentDTO(
            amount: 50.00,
            method: PaymentMethod::TALABAT_CARD,
            orderId: 3,
            shiftId: 2
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'amount' => 50.00,
            'method' => 'talabat_card',
            'order_id' => 3,
            'shift_id' => 2,
        ]);
    });

    it('throws exception when amount is zero', function () {
        expect(fn() => new PaymentDTO(
            amount: 0.00,
            method: PaymentMethod::CASH,
            orderId: 1,
            shiftId: 1
        ))->toThrow(InvalidArgumentException::class, 'Payment amount must be greater than zero');
    });

    it('throws exception when amount is negative', function () {
        expect(fn() => new PaymentDTO(
            amount: -10.00,
            method: PaymentMethod::CASH,
            orderId: 1,
            shiftId: 1
        ))->toThrow(InvalidArgumentException::class, 'Payment amount must be greater than zero');
    });

    it('accepts different payment methods', function () {
        $cashPayment = new PaymentDTO(10.00, PaymentMethod::CASH, 1, 1);
        $cardPayment = new PaymentDTO(20.00, PaymentMethod::CARD, 1, 1);
        $talabatPayment = new PaymentDTO(30.00, PaymentMethod::TALABAT_CARD, 1, 1);

        expect($cashPayment->method)->toBe(PaymentMethod::CASH)
            ->and($cardPayment->method)->toBe(PaymentMethod::CARD)
            ->and($talabatPayment->method)->toBe(PaymentMethod::TALABAT_CARD);
    });
});
