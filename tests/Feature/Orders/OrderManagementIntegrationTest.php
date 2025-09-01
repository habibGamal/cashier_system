<?php

use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\Orders\OrderCreated;
use App\Events\Orders\OrderCompleted;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\Shift;
use App\Models\Driver;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('Order Management Integration', function () {
    beforeEach(function () {
        Event::fake();

        // Register the OrderServiceProvider for dependency injection
        app()->register(\App\Providers\OrderServiceProvider::class);

        $this->orderService = app(OrderService::class);
        $this->user = User::factory()->create();
        $this->shift = Shift::factory()->create();
        $this->driver = Driver::factory()->create();
        $this->product = Product::factory()->create([
            'price' => 25.00,
            'cost' => 15.00,
        ]);
        $this->customer = Customer::factory()->create();
    });

    describe('Complete Order Flow', function () {
        it('creates, updates, and completes a dine-in order', function () {
            // 1. Create order
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                tableNumber: 'T001'
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            expect($order)->toBeInstanceOf(Order::class)
                ->and($order->status)->toBe(OrderStatus::PROCESSING)
                ->and($order->payment_status)->toBe(PaymentStatus::PENDING)
                ->and($order->type)->toBe(OrderType::DINE_IN)
                ->and($order->dine_table_number)->toBe('T001');

            Event::assertDispatched(OrderCreated::class);

            // 2. Add items to order
            $itemsData = [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 25.00,
                    'cost' => 15.00,
                    'notes' => 'Extra cheese',
                ],
            ];

            $order = $this->orderService->updateOrderItems($order->id, $itemsData);

            expect($order->items)->toHaveCount(1)
                ->and($order->items->first()->quantity)->toBe(2)
                ->and($order->items->first()->total)->toBe(50);

            // 3. Link customer
            $order = $this->orderService->linkCustomer($order->id, $this->customer->id);
            expect($order->customer_id)->toBe($this->customer->id);

            // 4. Apply discount
            $order = $this->orderService->applyDiscount($order->id, 10.0, 'percentage');
            expect($order->discount)->toBeGreaterThan(0);

            // 5. Complete order with payment
            $paymentsData = [
                'cash' => 45.00,
            ];

            $order = $this->orderService->completeOrder($order->id, $paymentsData);

            expect($order->status)->toBe(OrderStatus::COMPLETED)
                ->and($order->payments)->toHaveCount(1)
                ->and($order->payments->first()->amount)->toBe('45.00');

            Event::assertDispatched(OrderCompleted::class);
        });

        it('creates and completes a takeaway order', function () {
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::TAKEAWAY,
                shiftId: $this->shift->id,
                userId: $this->user->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            expect($order->type)->toBe(OrderType::TAKEAWAY)
                ->and($order->dine_table_number)->toBeNull();

            // Add items and complete
            $itemsData = [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 25.00,
                    'cost' => 15.00,
                ],
            ];

            $order = $this->orderService->updateOrderItems($order->id, $itemsData);

            $paymentsData = ['card' => 25.00];
            $order = $this->orderService->completeOrder($order->id, $paymentsData);

            expect($order->status)->toBe(OrderStatus::COMPLETED)
                ->and($order->payment_status)->toBe(PaymentStatus::FULL_PAID);
        });

        it('handles multiple payment methods', function () {
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DELIVERY,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                driverId: $this->driver->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 4,
                    'price' => 25.00,
                    'cost' => 15.00,
                ],
            ];

            $order = $this->orderService->updateOrderItems($order->id, $itemsData);

            $paymentsData = [
                'cash' => 60.00,
                'card' => 40.00,
            ];

            $order = $this->orderService->completeOrder($order->id, $paymentsData);

            expect($order->payments)->toHaveCount(2)
                ->and($order->payments->sum('amount'))->toBe(100.0)
                ->and($order->payment_status)->toBe(PaymentStatus::FULL_PAID);
        });
    });

    describe('Order Modifications', function () {
        beforeEach(function () {
            $this->order = Order::factory()->create([
                'status' => OrderStatus::PROCESSING,
                'shift_id' => $this->shift->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('updates order notes', function () {
            $order = $this->orderService->updateNotes(
                $this->order->id,
                'No onions',
                'Customer allergic to onions'
            );

            expect($order->kitchen_notes)->toBe('No onions')
                ->and($order->order_notes)->toBe('Customer allergic to onions');
        });

        it('links and unlinks customer', function () {
            // Link customer
            $order = $this->orderService->linkCustomer($this->order->id, $this->customer->id);
            expect($order->customer_id)->toBe($this->customer->id);

            // Unlink customer (link to null)
            $order = $this->orderService->linkCustomer($this->order->id, null);
            expect($order->customer_id)->toBeNull();
        });

        it('applies different discount types', function () {
            // Percentage discount
            $order = $this->orderService->applyDiscount($this->order->id, 15.0, 'percentage');
            $percentageDiscount = $order->discount;

            // Fixed discount
            $order = $this->orderService->applyDiscount($this->order->id, 20.0, 'fixed');
            $fixedDiscount = $order->discount;

            expect($percentageDiscount)->toBeGreaterThan(0)
                ->and($fixedDiscount)->toBeGreaterThan(0)
                ->and($percentageDiscount)->not->toBe($fixedDiscount);
        });
    });

    describe('Order Validation', function () {
        it('prevents operations on completed orders', function () {
            $order = Order::factory()->create([
                'status' => OrderStatus::COMPLETED,
            ]);

            // Should throw exception when trying to modify completed order
            expect(fn() => $this->orderService->updateOrderItems($order->id, []))
                ->toThrow(Exception::class);
        });

        it('validates table requirements for dine-in orders', function () {
            expect(fn() => new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id
            ))->toThrow(InvalidArgumentException::class, 'Table number is required for dine-in orders');
        });
    });

    describe('Order Calculations', function () {
        it('calculates order totals correctly', function () {
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                tableNumber: 'T001'
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 25.00,
                    'cost' => 15.00,
                ],
                [
                    'product_id' => Product::factory()->create(['price' => 15.00, 'cost' => 8.00])->id,
                    'quantity' => 1,
                    'price' => 15.00,
                    'cost' => 8.00,
                ],
            ];

            $order = $this->orderService->updateOrderItems($order->id, $itemsData);

            // Subtotal should be (2 * 25) + (1 * 15) = 65
            expect($order->sub_total)->toBe('65.00');

            // Service charge for dine-in (10% of subtotal)
            expect($order->service)->toBe('6.50');

            // Total should include service charge
            expect($order->total)->toBe('71.50');
        });

        it('calculates profit correctly', function () {
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::TAKEAWAY,
                shiftId: $this->shift->id,
                userId: $this->user->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 25.00,
                    'cost' => 15.00, // Profit per item: 10.00
                ],
            ];

            $order = $this->orderService->updateOrderItems($order->id, $itemsData);

            // Profit should be (25 - 15) * 2 = 20.00
            expect($order->profit)->toBe('20.00');
        });
    });
});
