<?php

use Tests\Unit\TestCase;
use App\Actions\Orders\CompleteOrderAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;

uses(Tests\Unit\TestCase::class);

describe('CompleteOrderAction', function () {
    beforeEach(function () {
        $this->action = app(CompleteOrderAction::class);
        $this->order = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
            'payment_status' => PaymentStatus::PENDING,
            'discount' => 0.00,
            'temp_discount_percent' => 0.00,
            'total' => 100.00,
        ]);

        // Add order items to ensure the total is calculated correctly
        $product1 = \App\Models\Product::factory()->create(['price' => 80.00, 'cost' => 40.00]);
        $product2 = \App\Models\Product::factory()->create(['price' => 20.00, 'cost' => 10.00]);

        \App\Models\OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'price' => 80.00,
            'cost' => 40.00,
            'total' => 80.00,
        ]);
        \App\Models\OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 20.00,
            'cost' => 10.00,
            'total' => 20.00,
        ]);
    });

    it('completes order with single payment method', function () {
        $paymentsData = [
            'cash' => 150.00,
        ];

        $result = $this->action->execute($this->order->id, $paymentsData);

        expect($result)->toBeInstanceOf(Order::class)
            ->and($result->status)->toBe(OrderStatus::COMPLETED)
            ->and($result->payment_status)->toBe(PaymentStatus::FULL_PAID);
    });

    it('completes order with multiple payment methods', function () {
        $paymentsData = [
            'cash' => 70.00,
            'card' => 50.00,
        ];

        $result = $this->action->execute($this->order->id, $paymentsData);

        expect($result->status)->toBe(OrderStatus::COMPLETED)
            ->and($result->payment_status)->toBe(PaymentStatus::FULL_PAID);
    });

    it('handles partial payment', function () {
        $paymentsData = [
            'cash' => 50.00, // Pay less than the 100.00 total
        ];

        $result = $this->action->execute($this->order->id, $paymentsData);

        expect($result->status)->toBe(OrderStatus::COMPLETED)
            ->and($result->payment_status)->toBe(PaymentStatus::PARTIAL_PAID);
    });

    it('can specify print option', function () {
        $paymentsData = [
            'cash' => 100.00,
        ];

        $result = $this->action->execute($this->order->id, $paymentsData, true);

        expect($result->status)->toBe(OrderStatus::COMPLETED);
    });
});
