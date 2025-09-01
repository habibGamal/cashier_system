<?php

use Tests\Unit\TestCase;
use App\Repositories\OrderRepository;
use App\DTOs\Orders\CreateOrderDTO;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;

uses(Tests\Unit\TestCase::class);

describe('OrderRepository', function () {
    beforeEach(function () {
        $this->repository = app(OrderRepository::class);
    });

    describe('create', function () {
        it('creates order from DTO', function () {
            $user = \App\Models\User::factory()->create();
            $shift = \App\Models\Shift::factory()->create();

            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $shift->id,
                userId: $user->id,
                tableNumber: 'T001'
            );

            $order = $this->repository->create($createOrderDTO);

            expect($order)->toBeInstanceOf(Order::class)
                ->and($order->type)->toBe(OrderType::DINE_IN)
                ->and($order->shift_id)->toBe($shift->id)
                ->and($order->user_id)->toBe($user->id)
                ->and($order->dine_table_number)->toBe('T001')
                ->and($order->status)->toBe(OrderStatus::PROCESSING)
                ->and($order->payment_status)->toBe(PaymentStatus::PENDING);
        });

        it('generates sequential order numbers', function () {
            $user = \App\Models\User::factory()->create();
            $shift = \App\Models\Shift::factory()->create();

            $dto1 = new CreateOrderDTO(OrderType::TAKEAWAY, $shift->id, $user->id);
            $dto2 = new CreateOrderDTO(OrderType::TAKEAWAY, $shift->id, $user->id);

            $order1 = $this->repository->create($dto1);
            $order2 = $this->repository->create($dto2);

            expect($order1->order_number)->toBe(1)
                ->and($order2->order_number)->toBe(2);
        });

        it('generates order numbers per shift', function () {
            $user = \App\Models\User::factory()->create();
            $shift1 = \App\Models\Shift::factory()->create();
            $shift2 = \App\Models\Shift::factory()->create();

            $dto1 = new CreateOrderDTO(OrderType::TAKEAWAY, $shift1->id, $user->id);
            $dto2 = new CreateOrderDTO(OrderType::TAKEAWAY, $shift2->id, $user->id);

            $order1 = $this->repository->create($dto1);
            $order2 = $this->repository->create($dto2);

            expect($order1->order_number)->toBe(1)
                ->and($order2->order_number)->toBe(1);
        });
    });

    describe('findById', function () {
        it('finds existing order', function () {
            $order = Order::factory()->create();

            $found = $this->repository->findById($order->id);

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($order->id);
        });

        it('returns null for non-existing order', function () {
            $found = $this->repository->findById(999);

            expect($found)->toBeNull();
        });
    });

    describe('findByIdOrFail', function () {
        it('finds existing order', function () {
            $order = Order::factory()->create();

            $found = $this->repository->findByIdOrFail($order->id);

            expect($found->id)->toBe($order->id);
        });

        it('throws exception for non-existing order', function () {
            expect(fn() => $this->repository->findByIdOrFail(999))
                ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getShiftOrders', function () {
        it('returns orders for specific shift', function () {
            $shift1 = \App\Models\Shift::factory()->create();
            $shift2 = \App\Models\Shift::factory()->create();

            Order::factory()->create(['shift_id' => $shift1->id]);
            Order::factory()->create(['shift_id' => $shift1->id]);
            Order::factory()->create(['shift_id' => $shift2->id]);

            $orders = $this->repository->getShiftOrders($shift1->id);

            expect($orders)->toHaveCount(2);
            expect($orders->every(fn($order) => $order->shift_id === $shift1->id))->toBeTrue();
        });

        it('returns empty collection when no orders', function () {
            $orders = $this->repository->getShiftOrders(999);

            expect($orders)->toHaveCount(0);
        });
    });

    describe('getProcessingOrders', function () {
        it('returns only processing orders for shift', function () {
            $shift = \App\Models\Shift::factory()->create();

            Order::factory()->create(['shift_id' => $shift->id, 'status' => OrderStatus::PROCESSING]);
            Order::factory()->create(['shift_id' => $shift->id, 'status' => OrderStatus::COMPLETED]);
            Order::factory()->create(['shift_id' => $shift->id, 'status' => OrderStatus::PROCESSING]);

            $orders = $this->repository->getProcessingOrders($shift->id);

            expect($orders)->toHaveCount(2);
            expect($orders->every(fn($order) => $order->status === OrderStatus::PROCESSING))->toBeTrue();
        });
    });

    describe('getNextOrderNumber', function () {
        it('returns 1 for first order in shift', function () {
            $shift = \App\Models\Shift::factory()->create();

            $orderNumber = $this->repository->getNextOrderNumber($shift->id);

            expect($orderNumber)->toBe(1);
        });

        it('returns incremented number for subsequent orders', function () {
            $shift = \App\Models\Shift::factory()->create();

            Order::factory()->create(['shift_id' => $shift->id, 'order_number' => 5]);

            $orderNumber = $this->repository->getNextOrderNumber($shift->id);

            expect($orderNumber)->toBe(6);
        });

        it('handles different shifts independently', function () {
            $shift1 = \App\Models\Shift::factory()->create();
            $shift2 = \App\Models\Shift::factory()->create();

            Order::factory()->create(['shift_id' => $shift1->id, 'order_number' => 5]);
            Order::factory()->create(['shift_id' => $shift2->id, 'order_number' => 3]);

            $orderNumber1 = $this->repository->getNextOrderNumber($shift1->id);
            $orderNumber2 = $this->repository->getNextOrderNumber($shift2->id);

            expect($orderNumber1)->toBe(6)
                ->and($orderNumber2)->toBe(4);
        });
    });

    describe('update', function () {
        it('updates order data', function () {
            $order = Order::factory()->create(['kitchen_notes' => 'Original notes']);

            $result = $this->repository->update($order, ['kitchen_notes' => 'Updated notes']);

            expect($result)->toBeTrue();
            expect($order->fresh()->kitchen_notes)->toBe('Updated notes');
        });
    });

    describe('delete', function () {
        it('deletes order', function () {
            $order = Order::factory()->create();

            $result = $this->repository->delete($order);

            expect($result)->toBeTrue();
            expect(Order::find($order->id))->toBeNull();
        });
    });

    describe('getOrdersWithPaymentStatus', function () {
        it('returns orders with specific payment status', function () {
            Order::factory()->create(['payment_status' => PaymentStatus::PENDING]);
            Order::factory()->create(['payment_status' => PaymentStatus::FULL_PAID]);
            Order::factory()->create(['payment_status' => PaymentStatus::PENDING]);

            $orders = $this->repository->getOrdersWithPaymentStatus(PaymentStatus::PENDING->value);

            expect($orders)->toHaveCount(2);
            expect($orders->every(fn($order) => $order->payment_status === PaymentStatus::PENDING))->toBeTrue();
        });
    });
});
