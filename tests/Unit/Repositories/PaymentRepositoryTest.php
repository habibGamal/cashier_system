<?php

use Tests\Unit\TestCase;
use App\Repositories\PaymentRepository;
use App\DTOs\Orders\PaymentDTO;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;

uses(Tests\Unit\TestCase::class);

describe('PaymentRepository', function () {
    beforeEach(function () {
        $this->repository = app(PaymentRepository::class);
        $this->order = Order::factory()->create();
    });

    describe('create', function () {
        it('creates payment from DTO', function () {
            $paymentDTO = new PaymentDTO(
                amount: 100.50,
                method: PaymentMethod::CASH,
                orderId: $this->order->id,
                shiftId: 1
            );

            $payment = $this->repository->create($paymentDTO);

            expect($payment)->toBeInstanceOf(Payment::class)
                ->and($payment->amount)->toBe('100.50')
                ->and($payment->method)->toBe(PaymentMethod::CASH)
                ->and($payment->order_id)->toBe($this->order->id)
                ->and($payment->shift_id)->toBe(1);
        });

        it('creates payment with different methods', function () {
            $cardPaymentDTO = new PaymentDTO(
                amount: 50.00,
                method: PaymentMethod::CARD,
                orderId: $this->order->id,
                shiftId: 1
            );

            $payment = $this->repository->create($cardPaymentDTO);

            expect($payment->method)->toBe(PaymentMethod::CARD);
        });
    });

    describe('getOrderPayments', function () {
        it('returns all payments for an order', function () {
            Payment::factory()->create([
                'order_id' => $this->order->id,
                'amount' => 50.00,
                'method' => PaymentMethod::CASH,
            ]);

            Payment::factory()->create([
                'order_id' => $this->order->id,
                'amount' => 25.00,
                'method' => PaymentMethod::CARD,
            ]);

            $otherOrder = Order::factory()->create();
            Payment::factory()->create(['order_id' => $otherOrder->id]);

            $payments = $this->repository->getOrderPayments($this->order->id);

            expect($payments)->toHaveCount(2)
                ->and($payments->every(fn($payment) => $payment->order_id === $this->order->id))->toBeTrue();
        });

        it('returns empty collection for order with no payments', function () {
            $payments = $this->repository->getOrderPayments($this->order->id);

            expect($payments)->toHaveCount(0);
        });
    });

    describe('getTotalPaidForOrder', function () {
        it('returns sum of all payments for order', function () {
            Payment::factory()->create([
                'order_id' => $this->order->id,
                'amount' => 60.00,
            ]);

            Payment::factory()->create([
                'order_id' => $this->order->id,
                'amount' => 40.00,
            ]);

            $total = $this->repository->getTotalPaidForOrder($this->order->id);

            expect($total)->toBe(100.00);
        });

        it('returns zero for order with no payments', function () {
            $total = $this->repository->getTotalPaidForOrder($this->order->id);

            expect($total)->toBe(0.0);
        });

        it('excludes payments from other orders', function () {
            Payment::factory()->create([
                'order_id' => $this->order->id,
                'amount' => 50.00,
            ]);

            $otherOrder = Order::factory()->create();
            Payment::factory()->create([
                'order_id' => $otherOrder->id,
                'amount' => 100.00,
            ]);

            $total = $this->repository->getTotalPaidForOrder($this->order->id);

            expect($total)->toBe(50.00);
        });
    });

    describe('deleteOrderPayments', function () {
        it('deletes all payments for an order', function () {
            Payment::factory()->create(['order_id' => $this->order->id]);
            Payment::factory()->create(['order_id' => $this->order->id]);

            $otherOrder = Order::factory()->create();
            $otherPayment = Payment::factory()->create(['order_id' => $otherOrder->id]);

            $result = $this->repository->deleteOrderPayments($this->order->id);

            expect($result)->toBeTrue()
                ->and(Payment::where('order_id', $this->order->id)->count())->toBe(0)
                ->and(Payment::where('order_id', $otherOrder->id)->count())->toBe(1);
        });

        it('returns true when no payments to delete', function () {
            $result = $this->repository->deleteOrderPayments($this->order->id);

            expect($result)->toBeTrue();
        });
    });
});
