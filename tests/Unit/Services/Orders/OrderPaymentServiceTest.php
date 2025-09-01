<?php

use App\Services\Orders\OrderPaymentService;
use App\DTOs\Orders\PaymentDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\Orders\PaymentProcessed;
use App\Models\Order;
use App\Models\Payment;
use Tests\Unit\TestCase;
use Illuminate\Support\Facades\Event;

uses(Tests\Unit\TestCase::class);

describe('OrderPaymentService', function () {
    beforeEach(function () {
        Event::fake();

        $this->service = app(OrderPaymentService::class);
        $this->order = Order::factory()->create([
            'total' => 100.00,
            'payment_status' => PaymentStatus::PENDING,
        ]);
    });

    it('creates payment and updates order status for full payment', function () {
        $paymentDTO = new PaymentDTO(
            amount: 100.00,
            method: PaymentMethod::CASH,
            orderId: $this->order->id,
            shiftId: 1
        );

        $result = $this->service->processPayment($this->order, $paymentDTO);

        expect($result->payment_status)->toBe(PaymentStatus::FULL_PAID)
            ->and(Payment::where('order_id', $this->order->id)->count())->toBe(1);

        Event::assertDispatched(PaymentProcessed::class);
    });

    it('sets partial paid status when payment is less than total', function () {
        $paymentDTO = new PaymentDTO(
            amount: 50.00,
            method: PaymentMethod::CASH,
            orderId: $this->order->id,
            shiftId: 1
        );

        $result = $this->service->processPayment($this->order, $paymentDTO);

        expect($result->payment_status)->toBe(PaymentStatus::PARTIAL_PAID)
            ->and(Payment::where('order_id', $this->order->id)->first()->amount)->toBe('50.00');
    });

    it('processes multiple payment methods', function () {
        $paymentsData = [
            'cash' => 60.00,
            'card' => 40.00,
        ];

        $payments = $this->service->processMultiplePayments($this->order, $paymentsData, 1);

        expect($payments)->toHaveCount(2)
            ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::FULL_PAID);

        $totalPaid = Payment::where('order_id', $this->order->id)->sum('amount');
        expect($totalPaid)->toBe(100);
    });

    it('skips zero amount payments', function () {
        $paymentsData = [
            'cash' => 100.00,
            'card' => 0.00,
        ];

        $payments = $this->service->processMultiplePayments($this->order, $paymentsData, 1);

        expect($payments)->toHaveCount(1)
            ->and(Payment::where('order_id', $this->order->id)->count())->toBe(1);
    });
});
