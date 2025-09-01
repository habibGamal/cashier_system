<?php

use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\MovementReason;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\Shift;
use App\Models\Category;
use App\Models\Printer;
use App\Services\Orders\OrderService;
use App\Services\Orders\OrderStockConversionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('Order Stock Conversion Feature Integration', function () {
    beforeEach(function () {
        // Register the OrderServiceProvider for dependency injection
        app()->register(\App\Providers\OrderServiceProvider::class);

        $this->orderService = app(OrderService::class);
        $this->stockService = app(StockService::class);
        $this->orderStockConversionService = app(OrderStockConversionService::class);

        // Create test data
        $this->user = User::factory()->create();
        $this->shift = Shift::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->category = Category::factory()->create();
        $this->printer = Printer::factory()->create();
    });

    describe('Stock Management Integration with Order Lifecycle', function () {
        it('manages stock for consumable products through complete order lifecycle', function () {
            // Create a consumable product
            $burger = Product::factory()->create([
                'name' => 'Beef Burger',
                'type' => ProductType::Consumable,
                'price' => 25.00,
                'cost' => 15.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create inventory for the product
            $inventoryItem = InventoryItem::factory()->create([
                'product_id' => $burger->id,
                'quantity' => 100,
            ]);

            // Create order with the consumable product
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id,
                tableNumber: 'T001'
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            // Add items to order
            $itemsData = [
                [
                    'product_id' => $burger->id,
                    'quantity' => 3,
                    'price' => 25.00,
                    'cost' => 15.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($order->id, $itemsData);

            // Verify initial stock
            $initialStock = $this->stockService->getCurrentStock($burger->id);
            expect($initialStock)->toBe(100.0);

            // Complete the order - this should reduce stock
            $paymentsData = ['cash' => 75.00]; // 3 * 25.00
            $this->orderService->completeOrder($order->id, $paymentsData);

            // Verify stock was reduced
            $stockAfterCompletion = $this->stockService->getCurrentStock($burger->id);
            expect($stockAfterCompletion)->toBe(97.0); // 100 - 3

            // Reload order to get updated status
            $order->refresh();
            expect($order->status)->toBe(OrderStatus::COMPLETED);

            // Cancel the completed order - this should restore stock
            $this->orderService->cancelOrder($order->id);

            // Verify stock was restored
            $stockAfterCancellation = $this->stockService->getCurrentStock($burger->id);
            expect($stockAfterCancellation)->toBe(100.0); // Back to original
        });

        it('manages stock for manufactured products with components', function () {
            // Create raw material components
            $flour = Product::factory()->create([
                'name' => 'Flour',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $sugar = Product::factory()->create([
                'name' => 'Sugar',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $eggs = Product::factory()->create([
                'name' => 'Eggs',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create manufactured product (cake)
            $cake = Product::factory()->create([
                'name' => 'Chocolate Cake',
                'type' => ProductType::Manufactured,
                'price' => 50.00,
                'cost' => 25.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Set up product components (recipe)
            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $flour->id,
                'quantity' => 2.0,
            ]);

            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $sugar->id,
                'quantity' => 1.0,
            ]);

            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $eggs->id,
                'quantity' => 3.0,
            ]);

            // Create inventory for raw materials
            InventoryItem::factory()->create([
                'product_id' => $flour->id,
                'quantity' => 50,
            ]);

            InventoryItem::factory()->create([
                'product_id' => $sugar->id,
                'quantity' => 30,
            ]);

            InventoryItem::factory()->create([
                'product_id' => $eggs->id,
                'quantity' => 100,
            ]);

            // Verify initial stock levels
            expect($this->stockService->getCurrentStock($flour->id))->toBe(50.0);
            expect($this->stockService->getCurrentStock($sugar->id))->toBe(30.0);
            expect($this->stockService->getCurrentStock($eggs->id))->toBe(100.0);

            // Create and complete an order with manufactured product
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::TAKEAWAY,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $cake->id,
                    'quantity' => 2, // Order 2 cakes
                    'price' => 50.00,
                    'cost' => 25.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($order->id, $itemsData);

            // Complete the order
            $paymentsData = ['cash' => 100.00]; // 2 * 50.00
            $this->orderService->completeOrder($order->id, $paymentsData);

            // Verify that raw materials were consumed according to recipe
            // 2 cakes * 2 flour per cake = 4 flour consumed
            expect($this->stockService->getCurrentStock($flour->id))->toBe(46.0); // 50 - 4

            // 2 cakes * 1 sugar per cake = 2 sugar consumed
            expect($this->stockService->getCurrentStock($sugar->id))->toBe(28.0); // 30 - 2

            // 2 cakes * 3 eggs per cake = 6 eggs consumed
            expect($this->stockService->getCurrentStock($eggs->id))->toBe(94.0); // 100 - 6

            // Cancel the order and verify stock restoration
            $order->refresh();
            $this->orderService->cancelOrder($order->id);

            // Verify stock was restored for all components
            expect($this->stockService->getCurrentStock($flour->id))->toBe(50.0);
            expect($this->stockService->getCurrentStock($sugar->id))->toBe(30.0);
            expect($this->stockService->getCurrentStock($eggs->id))->toBe(100.0);
        });

        it('handles nested manufactured products correctly', function () {
            // Create base raw materials
            $flour = Product::factory()->create([
                'name' => 'Flour',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $milk = Product::factory()->create([
                'name' => 'Milk',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create intermediate manufactured product (dough)
            $dough = Product::factory()->create([
                'name' => 'Bread Dough',
                'type' => ProductType::Manufactured,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Dough recipe: 3 flour + 1 milk
            ProductComponent::create([
                'product_id' => $dough->id,
                'component_id' => $flour->id,
                'quantity' => 3.0,
            ]);

            ProductComponent::create([
                'product_id' => $dough->id,
                'component_id' => $milk->id,
                'quantity' => 1.0,
            ]);

            // Create final manufactured product (bread)
            $bread = Product::factory()->create([
                'name' => 'Fresh Bread',
                'type' => ProductType::Manufactured,
                'price' => 15.00,
                'cost' => 8.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Bread recipe: 2 dough
            ProductComponent::create([
                'product_id' => $bread->id,
                'component_id' => $dough->id,
                'quantity' => 2.0,
            ]);

            // Create inventory for raw materials
            InventoryItem::factory()->create([
                'product_id' => $flour->id,
                'quantity' => 100,
            ]);

            InventoryItem::factory()->create([
                'product_id' => $milk->id,
                'quantity' => 50,
            ]);

            // Test stock conversion without completing order
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $bread->id,
                'quantity' => 1,
                'price' => 15.00,
                'cost' => 8.00,
                'total' => 15.00,
            ]);

            $stockItems = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            // Should break down: 1 bread * 2 dough * (3 flour + 1 milk) = 6 flour + 2 milk
            expect($stockItems)->toHaveCount(2);

            $flourItem = collect($stockItems)->firstWhere('product_id', $flour->id);
            $milkItem = collect($stockItems)->firstWhere('product_id', $milk->id);

            expect($flourItem['quantity'])->toBe(6.0); // 1 bread * 2 dough * 3 flour
            expect($milkItem['quantity'])->toBe(2.0); // 1 bread * 2 dough * 1 milk

            // Now test with actual order completion
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id,
                tableNumber: 'T005'
            );

            $realOrder = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $bread->id,
                    'quantity' => 1,
                    'price' => 15.00,
                    'cost' => 8.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($realOrder->id, $itemsData);
            $paymentsData = ['cash' => 15.00];
            $this->orderService->completeOrder($realOrder->id, $paymentsData);

            // Verify nested breakdown affected raw materials correctly
            expect($this->stockService->getCurrentStock($flour->id))->toBe(94.0); // 100 - 6
            expect($this->stockService->getCurrentStock($milk->id))->toBe(48.0); // 50 - 2
        });

        it('validates stock availability before order completion', function () {
            // Create a product with limited stock
            $limitedProduct = Product::factory()->create([
                'name' => 'Limited Edition Burger',
                'type' => ProductType::Consumable,
                'price' => 30.00,
                'cost' => 18.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create inventory with only 2 items
            InventoryItem::factory()->create([
                'product_id' => $limitedProduct->id,
                'quantity' => 2,
            ]);

            // Create order trying to buy 5 items
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::TAKEAWAY,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            $itemsData = [
                [
                    'product_id' => $limitedProduct->id,
                    'quantity' => 5, // More than available stock
                    'price' => 30.00,
                    'cost' => 18.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($order->id, $itemsData);

            // Check stock requirements
            $stockRequirements = $this->orderStockConversionService->getOrderStockRequirements($order);

            expect($stockRequirements)->toHaveCount(1);
            expect($stockRequirements[0]['product_id'])->toBe($limitedProduct->id);
            expect($stockRequirements[0]['required_quantity'])->toBe(5);
            expect($stockRequirements[0]['current_stock'])->toBe(2.0);
            expect($stockRequirements[0]['sufficient'])->toBeFalse();

            // Validate stock availability
            $insufficientStock = $this->orderStockConversionService->validateOrderStockAvailability($order);
            expect($insufficientStock)->toHaveCount(1);
            expect($insufficientStock[0]['product_name'])->toBe('Limited Edition Burger');

            // If ALLOW_INSUFFICIENT_STOCK is false, order completion should fail
            // This depends on the OrderService implementation
            $initialStock = $this->stockService->getCurrentStock($limitedProduct->id);
            expect($initialStock)->toBe(2.0);

            // Note: Actual behavior depends on OrderService::ALLOW_INSUFFICIENT_STOCK setting
            // The test verifies that the validation correctly identifies insufficient stock
        });

        it('optimizes stock operations for orders with multiple items using same components', function () {
            // Create raw materials
            $flour = Product::factory()->create([
                'name' => 'Flour',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $sugar = Product::factory()->create([
                'name' => 'Sugar',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create two different manufactured products that use the same components
            $cake = Product::factory()->create([
                'name' => 'Cake',
                'type' => ProductType::Manufactured,
                'price' => 25.00,
                'cost' => 12.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $cookies = Product::factory()->create([
                'name' => 'Cookies',
                'type' => ProductType::Manufactured,
                'price' => 15.00,
                'cost' => 8.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Cake recipe: 3 flour + 2 sugar
            ProductComponent::create(['product_id' => $cake->id, 'component_id' => $flour->id, 'quantity' => 3.0]);
            ProductComponent::create(['product_id' => $cake->id, 'component_id' => $sugar->id, 'quantity' => 2.0]);

            // Cookies recipe: 2 flour + 1 sugar
            ProductComponent::create(['product_id' => $cookies->id, 'component_id' => $flour->id, 'quantity' => 2.0]);
            ProductComponent::create(['product_id' => $cookies->id, 'component_id' => $sugar->id, 'quantity' => 1.0]);

            // Create inventory
            InventoryItem::factory()->create(['product_id' => $flour->id, 'quantity' => 100]);
            InventoryItem::factory()->create(['product_id' => $sugar->id, 'quantity' => 80]);

            // Create order with multiple items
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DINE_IN,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id,
                tableNumber: 'T010'
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            // Add 1 cake and 2 cookies
            $itemsData = [
                [
                    'product_id' => $cake->id,
                    'quantity' => 1,
                    'price' => 25.00,
                    'cost' => 12.00,
                    'notes' => null,
                ],
                [
                    'product_id' => $cookies->id,
                    'quantity' => 2,
                    'price' => 15.00,
                    'cost' => 8.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($order->id, $itemsData);

            // Test stock conversion optimization
            $stockItems = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            // Should optimize: cake (3 flour + 2 sugar) + 2*cookies (2*2 flour + 2*1 sugar)
            // Total: 7 flour (3 + 4), 4 sugar (2 + 2)
            expect($stockItems)->toHaveCount(2);

            $flourItem = collect($stockItems)->firstWhere('product_id', $flour->id);
            $sugarItem = collect($stockItems)->firstWhere('product_id', $sugar->id);

            expect($flourItem['quantity'])->toBe(7.0);
            expect($sugarItem['quantity'])->toBe(4.0);

            // Complete order and verify stock reduction
            $paymentsData = ['cash' => 55.00]; // 25 + 30
            $this->orderService->completeOrder($order->id, $paymentsData);

            expect($this->stockService->getCurrentStock($flour->id))->toBe(93.0); // 100 - 7
            expect($this->stockService->getCurrentStock($sugar->id))->toBe(76.0); // 80 - 4
        });

        it('handles orders with mixed product types correctly', function () {
            // Create consumable product
            $drink = Product::factory()->create([
                'name' => 'Soft Drink',
                'type' => ProductType::Consumable,
                'price' => 5.00,
                'cost' => 2.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create raw material (should not affect stock directly)
            $rawMaterial = Product::factory()->create([
                'name' => 'Raw Ingredient',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create manufactured product with components
            $sandwich = Product::factory()->create([
                'name' => 'Club Sandwich',
                'type' => ProductType::Manufactured,
                'price' => 20.00,
                'cost' => 10.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $bread = Product::factory()->create([
                'name' => 'Bread',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Sandwich recipe
            ProductComponent::create(['product_id' => $sandwich->id, 'component_id' => $bread->id, 'quantity' => 2.0]);

            // Create inventory
            InventoryItem::factory()->create(['product_id' => $drink->id, 'quantity' => 50]);
            InventoryItem::factory()->create(['product_id' => $rawMaterial->id, 'quantity' => 30]);
            InventoryItem::factory()->create(['product_id' => $bread->id, 'quantity' => 40]);

            // Create order with mixed product types
            $createOrderDTO = new CreateOrderDTO(
                type: OrderType::DELIVERY,
                shiftId: $this->shift->id,
                userId: $this->user->id,
                customerId: $this->customer->id
            );

            $order = $this->orderService->createOrder($createOrderDTO);

            // Add all product types to order
            $itemsData = [
                [
                    'product_id' => $drink->id,
                    'quantity' => 2,
                    'price' => 5.00,
                    'cost' => 2.00,
                    'notes' => null,
                ],
                [
                    'product_id' => $rawMaterial->id,
                    'quantity' => 1,
                    'price' => 3.00,
                    'cost' => 1.00,
                    'notes' => null,
                ],
                [
                    'product_id' => $sandwich->id,
                    'quantity' => 1,
                    'price' => 20.00,
                    'cost' => 10.00,
                    'notes' => null,
                ],
            ];

            $this->orderService->updateOrderItems($order->id, $itemsData);

            $initialDrinkStock = $this->stockService->getCurrentStock($drink->id);
            $initialRawMaterialStock = $this->stockService->getCurrentStock($rawMaterial->id);
            $initialBreadStock = $this->stockService->getCurrentStock($bread->id);

            // Complete order
            $paymentsData = ['cash' => 32.00]; // 10 + 3 + 20 - 1 (discount estimate)
            $this->orderService->completeOrder($order->id, $paymentsData);

            // Verify stock changes:
            // - Consumable (drink): should decrease by quantity ordered
            expect($this->stockService->getCurrentStock($drink->id))->toBe($initialDrinkStock - 2);

            // - Raw material (when sold directly): should NOT decrease (logged as warning)
            expect($this->stockService->getCurrentStock($rawMaterial->id))->toBe($initialRawMaterialStock);

            // - Manufactured product components (bread): should decrease by recipe quantity
            expect($this->stockService->getCurrentStock($bread->id))->toBe($initialBreadStock - 2);
        });
    });

    describe('Error Handling and Edge Cases', function () {
        it('handles orders with no items gracefully', function () {
            $order = Order::factory()->create();

            $stockItems = $this->orderStockConversionService->convertOrderItemsToStockItems($order);
            expect($stockItems)->toBeArray();
            expect($stockItems)->toHaveCount(0);

            $stockRequirements = $this->orderStockConversionService->getOrderStockRequirements($order);
            expect($stockRequirements)->toBeArray();
            expect($stockRequirements)->toHaveCount(0);

            $insufficientStock = $this->orderStockConversionService->validateOrderStockAvailability($order);
            expect($insufficientStock)->toBeArray();
            expect($insufficientStock)->toHaveCount(0);
        });

        it('handles products without inventory items', function () {
            $productWithoutInventory = Product::factory()->create([
                'name' => 'New Product',
                'type' => ProductType::Consumable,
                'price' => 10.00,
                'cost' => 5.00,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $productWithoutInventory->id,
                'quantity' => 1,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 10.00,
            ]);

            $stockRequirements = $this->orderStockConversionService->getOrderStockRequirements($order);
            expect($stockRequirements)->toHaveCount(1);
            expect($stockRequirements[0]['current_stock'])->toBe(0.0);
            expect($stockRequirements[0]['sufficient'])->toBeFalse();
        });

        it('logs warnings for problematic scenarios', function () {
            Log::shouldReceive('warning')
                ->once()
                ->with('Raw material found in order items', \Mockery::on(function ($context) {
                    return isset($context['order_id']) && isset($context['product_id']) && isset($context['product_name']);
                }));

            $rawMaterial = Product::factory()->create([
                'name' => 'Raw Material',
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $rawMaterial->id,
                'quantity' => 1,
                'price' => 5.00,
                'cost' => 2.00,
                'total' => 5.00,
            ]);

            $this->orderStockConversionService->convertOrderItemsToStockItems($order);
        });
    });
});
