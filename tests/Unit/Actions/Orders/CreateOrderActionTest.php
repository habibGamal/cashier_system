<?php

use Tests\Unit\TestCase;
use App\Actions\Orders\CreateOrderAction;
use App\DTOs\Orders\CreateOrderDTO;
use App\Enums\OrderType;
use App\Models\Order;
use App\Services\Orders\OrderService;

uses(Tests\Unit\TestCase::class);

describe('CreateOrderAction', function () {
    beforeEach(function () {
        $this->action = app(CreateOrderAction::class);
        $this->user = \App\Models\User::factory()->create();
        $this->shift = \App\Models\Shift::factory()->create();
        $this->driver = \App\Models\Driver::factory()->create();
    });

    it('delegates to order service', function () {
        $createOrderDTO = new CreateOrderDTO(
            type: OrderType::TAKEAWAY,
            shiftId: $this->shift->id,
            userId: $this->user->id
        );

        $result = $this->action->execute($createOrderDTO);

        expect($result)->toBeInstanceOf(Order::class);
    });

    it('creates order with correct data', function () {
        $createOrderDTO = new CreateOrderDTO(
            type: OrderType::DINE_IN,
            shiftId: $this->shift->id,
            userId: $this->user->id,
            tableNumber: 'T001'
        );

        $order = $this->action->execute($createOrderDTO);

        expect($order)->toBeInstanceOf(Order::class)
            ->and($order->type)->toBe(OrderType::DINE_IN)
            ->and($order->shift_id)->toBe($this->shift->id)
            ->and($order->user_id)->toBe($this->user->id)
            ->and($order->dine_table_number)->toBe('T001');
    });

    it('creates takeaway order without table', function () {
        $createOrderDTO = new CreateOrderDTO(
            type: OrderType::TAKEAWAY,
            shiftId: $this->shift->id,
            userId: $this->user->id
        );

        $order = $this->action->execute($createOrderDTO);

        expect($order)->toBeInstanceOf(Order::class)
            ->and($order->type)->toBe(OrderType::TAKEAWAY)
            ->and($order->dine_table_number)->toBeNull();
    });

    it('creates delivery order with driver', function () {
        $createOrderDTO = new CreateOrderDTO(
            type: OrderType::DELIVERY,
            shiftId: $this->shift->id,
            userId: $this->user->id,
            driverId: $this->driver->id
        );

        $order = $this->action->execute($createOrderDTO);

        expect($order)->toBeInstanceOf(Order::class)
            ->and($order->type)->toBe(OrderType::DELIVERY)
            ->and($order->driver_id)->toBe($this->driver->id);
    });
});
