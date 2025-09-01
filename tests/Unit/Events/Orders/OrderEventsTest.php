<?php

use Tests\Unit\TestCase;
use App\Events\Orders\OrderCreated;
use App\Events\Orders\OrderCompleted;
use App\Events\Orders\OrderCancelled;
use App\Events\Orders\PaymentProcessed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shift;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Illuminate\Broadcasting\PrivateChannel;

uses(Tests\Unit\TestCase::class);

describe('Order Events', function () {
    describe('OrderCreated Event', function () {
        it('broadcasts on correct channels', function () {
            $shift = Shift::factory()->create();
            $order = Order::factory()->create(['shift_id' => $shift->id]);
            $event = new OrderCreated($order);

            $channels = $event->broadcastOn();

            expect($channels)->toHaveCount(2);
            expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
            expect($channels[0]->name)->toBe("private-shift.{$shift->id}");
            expect($channels[1]->name)->toBe('private-kitchen');
        });

        it('broadcasts correct data', function () {
            $order = Order::factory()->create([
                'order_number' => 123,
                'dine_table_number' => 'T001',
                'type' => OrderType::DINE_IN,
            ]);

            $event = new OrderCreated($order);
            $data = $event->broadcastWith();

            expect($data['order']['id'])->toBe($order->id);
            expect($data['order']['order_number'])->toBe(123);
            expect($data['order']['type'])->toBe('صالة');
            expect($data['order']['table_number'])->toBe('T001');
            expect($data['order'])->toHaveKey('created_at');
        });

        it('broadcasts with correct event name', function () {
            $order = Order::factory()->create();
            $event = new OrderCreated($order);

            expect($event->broadcastAs())->toBe('order.created');
        });
    });

    describe('OrderCompleted Event', function () {
        it('broadcasts on correct channels', function () {
            $shift = Shift::factory()->create();
            $order = Order::factory()->create(['shift_id' => $shift->id]);
            $event = new OrderCompleted($order);

            $channels = $event->broadcastOn();

            expect($channels)->toHaveCount(2);
            expect($channels[0]->name)->toBe("private-shift.{$shift->id}");
            expect($channels[1]->name)->toBe('private-kitchen');
        });

        it('broadcasts correct data', function () {
            $order = Order::factory()->create([
                'order_number' => 456,
                'total' => 75.50,
            ]);

            $event = new OrderCompleted($order);
            $data = $event->broadcastWith();

            expect($data['order']['id'])->toBe($order->id);
            expect($data['order']['order_number'])->toBe(456);
            expect($data['order']['total'])->toBe('75.50');
            expect($data['order'])->toHaveKey('completed_at');
        });

        it('broadcasts with correct event name', function () {
            $order = Order::factory()->create();
            $event = new OrderCompleted($order);

            expect($event->broadcastAs())->toBe('order.completed');
        });
    });

    describe('OrderCancelled Event', function () {
        it('broadcasts on correct channels', function () {
            $shift = Shift::factory()->create();
            $order = Order::factory()->create(['shift_id' => $shift->id]);
            $event = new OrderCancelled($order, 'Customer changed mind');

            $channels = $event->broadcastOn();

            expect($channels)->toHaveCount(2);
            expect($channels[0]->name)->toBe("private-shift.{$shift->id}");
            expect($channels[1]->name)->toBe('private-kitchen');
        });

        it('broadcasts correct data with reason', function () {
            $order = Order::factory()->create(['order_number' => 789]);
            $reason = 'Customer changed mind';

            $event = new OrderCancelled($order, $reason);
            $data = $event->broadcastWith();

            expect($data['order']['id'])->toBe($order->id);
            expect($data['order']['order_number'])->toBe(789);
            expect($data['order']['reason'])->toBe($reason);
            expect($data['order'])->toHaveKey('cancelled_at');
        });

        it('broadcasts with empty reason when not provided', function () {
            $order = Order::factory()->create();
            $event = new OrderCancelled($order);

            $data = $event->broadcastWith();

            expect($data['order']['reason'])->toBe('');
        });

        it('broadcasts with correct event name', function () {
            $order = Order::factory()->create();
            $event = new OrderCancelled($order);

            expect($event->broadcastAs())->toBe('order.cancelled');
        });
    });

    describe('PaymentProcessed Event', function () {
        it('broadcasts on correct channels', function () {
            $shift = Shift::factory()->create();
            $order = Order::factory()->create(['shift_id' => $shift->id]);
            $payment = Payment::factory()->create(['order_id' => $order->id]);

            $event = new PaymentProcessed($payment, $order);

            $channels = $event->broadcastOn();

            expect($channels)->toHaveCount(1);
            expect($channels[0]->name)->toBe("private-shift.{$shift->id}");
        });

        it('broadcasts correct payment data', function () {
            $order = Order::factory()->create();
            $payment = Payment::factory()->create([
                'order_id' => $order->id,
                'amount' => 100.00,
                'method' => PaymentMethod::CASH,
            ]);

            $event = new PaymentProcessed($payment, $order);
            $data = $event->broadcastWith();

            expect($data['payment']['id'])->toBe($payment->id);
            expect($data['payment']['amount'])->toBe('100.00');
            expect($data['payment']['method'])->toBe('نقدي');
            expect($data['payment']['order_id'])->toBe($order->id);
            expect($data['payment'])->toHaveKey('processed_at');
        });

        it('broadcasts with correct event name', function () {
            $order = Order::factory()->create();
            $payment = Payment::factory()->create(['order_id' => $order->id]);

            $event = new PaymentProcessed($payment, $order);

            expect($event->broadcastAs())->toBe('payment.processed');
        });

        it('shows correct payment method labels', function () {
            $order = Order::factory()->create();

            $cashPayment = Payment::factory()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::CASH,
            ]);

            $cardPayment = Payment::factory()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::CARD,
            ]);

            $cashEvent = new PaymentProcessed($cashPayment, $order);
            $cardEvent = new PaymentProcessed($cardPayment, $order);

            expect($cashEvent->broadcastWith()['payment']['method'])->toBe('نقدي');
            expect($cardEvent->broadcastWith()['payment']['method'])->toBe('بطاقة');
        });
    });
});
