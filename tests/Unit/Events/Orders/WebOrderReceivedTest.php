<?php

use Tests\Unit\TestCase;
use App\Events\Orders\WebOrderReceived;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Shift;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use Illuminate\Broadcasting\PrivateChannel;

uses(Tests\Unit\TestCase::class);

describe('WebOrderReceived Event', function () {
    it('broadcasts on correct channel', function () {
        $shift = Shift::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'type' => OrderType::WEB_DELIVERY,
            'status' => OrderStatus::PENDING,
        ]);

        $event = new WebOrderReceived($order);
        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1);
        expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
        expect($channels[0]->name)->toBe('private-web-orders');
    });

    it('broadcasts correct order data', function () {
        $shift = Shift::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'أحمد محمد',
            'phone' => '01234567890',
            'address' => 'القاهرة، مصر',
        ]);
        $order = Order::factory()->create([
            'shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'order_number' => 'WEB001',
            'type' => OrderType::WEB_DELIVERY,
            'status' => OrderStatus::PENDING,
            'total' => 150.50,
            'order_notes' => 'بدون بصل',
        ]);

        $event = new WebOrderReceived($order);
        $data = $event->broadcastWith();

        expect($data['order']['id'])->toBe($order->id);
        expect($data['order']['order_number'])->toBe('WEB001');
        expect($data['order']['type'])->toBe('ويب دليفري');
        expect($data['order']['typeString'])->toBe('ويب دليفري');
        expect($data['order']['customer_name'])->toBe('أحمد محمد');
        expect($data['order']['customer_phone'])->toBe('01234567890');
        expect($data['order']['customer_address'])->toBe('القاهرة، مصر');
        expect($data['order']['total'])->toBe(150.50);
        expect($data['order']['status'])->toBe('في الإنتظار');
        expect($data['order']['order_notes'])->toBe('بدون بصل');
        expect($data['order'])->toHaveKey('created_at');
        expect($data['order'])->toHaveKey('items_count');
    });

    it('broadcasts with correct event name', function () {
        $order = Order::factory()->create();
        $event = new WebOrderReceived($order);

        expect($event->broadcastAs())->toBe('web-order.received');
    });

    it('handles order with items', function () {
        $order = Order::factory()->hasItems(3)->create();
        $event = new WebOrderReceived($order);

        $data = $event->broadcastWith();

        expect($data['order']['items_count'])->toBe(3);
    });
});
