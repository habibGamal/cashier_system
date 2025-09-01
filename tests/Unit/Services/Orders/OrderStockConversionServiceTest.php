<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Category;
use App\Models\Printer;
use App\Models\InventoryItem;
use App\Services\Orders\OrderStockConversionService;
use App\Services\StockService;
use App\Enums\ProductType;
use App\Enums\MovementReason;
use Tests\Unit\TestCase;
use Illuminate\Support\Facades\Log;

uses(Tests\Unit\TestCase::class);

describe('OrderStockConversionService', function () {
    beforeEach(function () {
        // Mock StockService
        $this->stockService = \Mockery::mock(StockService::class);
        $this->orderStockConversionService = new OrderStockConversionService($this->stockService);

        // Create test categories and printer
        $this->category = Category::factory()->create();
        $this->printer = Printer::factory()->create();
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('convertOrderItemsToStockItems', function () {
        it('converts consumable products directly to stock items', function () {
            // Create consumable product
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create order with consumable item
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 3,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 30.00, // quantity * price
            ]);

            $order->load('items.product');

            $result = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            expect($result)->toHaveCount(1);
            expect($result[0])->toBe([
                'product_id' => $consumableProduct->id,
                'quantity' => 3,
            ]);
        });

        it('logs warning for raw material products in orders', function () {
            Log::shouldReceive('warning')->once()
                ->with('Raw material found in order items', \Mockery::type('array'));

            // Create raw material product
            $rawProduct = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create order with raw material item
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $rawProduct->id,
                'quantity' => 2,
                'price' => 15.00,
                'cost' => 8.00,
                'total' => 0.00, // quantity * price
                'total' => 30.00, // quantity * price
            ]);

            $order->load('items.product');

            $result = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            expect($result)->toBeEmpty();
        });

        it('breaks down manufactured products into their components', function () {
            // Create raw material components
            $flour = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Flour',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $sugar = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Sugar',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create consumable component
            $eggs = Product::factory()->create([
                'type' => ProductType::Consumable,
                'name' => 'Eggs',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create manufactured product (cake)
            $cake = Product::factory()->create([
                'type' => ProductType::Manufactured,
                'name' => 'Cake',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create product components (recipe)
            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $flour->id,
                'quantity' => 2, // 2 units of flour per cake
            ]);

            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $sugar->id,
                'quantity' => 1, // 1 unit of sugar per cake
            ]);

            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $eggs->id,
                'quantity' => 3, // 3 units of eggs per cake
            ]);

            // Create order with 2 cakes
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $cake->id,
                'quantity' => 2,
                'price' => 50.00,
                'cost' => 25.00,
                'total' => 0.00, // quantity * price
                'total' => 100.00, // quantity * price
            ]);

            $order->load('items.product');

            $result = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            expect($result)->toHaveCount(3);

            // Find each component in the result
            $flourItem = collect($result)->firstWhere('product_id', $flour->id);
            $sugarItem = collect($result)->firstWhere('product_id', $sugar->id);
            $eggsItem = collect($result)->firstWhere('product_id', $eggs->id);

            expect($flourItem['quantity'])->toBe(4.0); // 2 cakes * 2 flour each
            expect($sugarItem['quantity'])->toBe(2.0); // 2 cakes * 1 sugar each
            expect($eggsItem['quantity'])->toBe(6.0); // 2 cakes * 3 eggs each
        });

        it('handles nested manufactured products recursively', function () {
            // Create raw materials
            $flour = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Flour',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $milk = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Milk',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create intermediate manufactured product (dough)
            $dough = Product::factory()->create([
                'type' => ProductType::Manufactured,
                'name' => 'Dough',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Dough recipe: 3 flour + 1 milk
            ProductComponent::create([
                'product_id' => $dough->id,
                'component_id' => $flour->id,
                'quantity' => 3,
            ]);

            ProductComponent::create([
                'product_id' => $dough->id,
                'component_id' => $milk->id,
                'quantity' => 1,
            ]);

            // Create final manufactured product (bread)
            $bread = Product::factory()->create([
                'type' => ProductType::Manufactured,
                'name' => 'Bread',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Bread recipe: 2 dough (which will be broken down further)
            ProductComponent::create([
                'product_id' => $bread->id,
                'component_id' => $dough->id,
                'quantity' => 2,
            ]);

            // Create order with 1 bread
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $bread->id,
                'quantity' => 1,
                'price' => 20.00,
                'cost' => 10.00,
                'total' => 0.00, // quantity * price
                'total' => 20.00, // quantity * price
            ]);

            $order->load('items.product');

            $result = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            expect($result)->toHaveCount(2);

            // Find each component in the result
            $flourItem = collect($result)->firstWhere('product_id', $flour->id);
            $milkItem = collect($result)->firstWhere('product_id', $milk->id);

            expect($flourItem['quantity'])->toBe(6.0); // 1 bread * 2 dough * 3 flour each
            expect($milkItem['quantity'])->toBe(2.0); // 1 bread * 2 dough * 1 milk each
        });

        it('optimizes stock items by summing common components', function () {
            // Create raw materials
            $flour = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Flour',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $sugar = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'name' => 'Sugar',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Create two different manufactured products using same components
            $cake = Product::factory()->create([
                'type' => ProductType::Manufactured,
                'name' => 'Cake',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $cookies = Product::factory()->create([
                'type' => ProductType::Manufactured,
                'name' => 'Cookies',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            // Cake recipe: 3 flour + 2 sugar
            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $flour->id,
                'quantity' => 3,
            ]);

            ProductComponent::create([
                'product_id' => $cake->id,
                'component_id' => $sugar->id,
                'quantity' => 2,
            ]);

            // Cookies recipe: 2 flour + 1 sugar
            ProductComponent::create([
                'product_id' => $cookies->id,
                'component_id' => $flour->id,
                'quantity' => 2,
            ]);

            ProductComponent::create([
                'product_id' => $cookies->id,
                'component_id' => $sugar->id,
                'quantity' => 1,
            ]);

            // Create order with both products
            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $cake->id,
                'quantity' => 1,
                'price' => 25.00,
                'cost' => 12.00,
                'total' => 0.00, // quantity * price
                'total' => 25.00, // quantity * price
            ]);

            $order->items()->create([
                'product_id' => $cookies->id,
                'quantity' => 2,
                'price' => 15.00,
                'cost' => 8.00,
                'total' => 0.00, // quantity * price
                'total' => 30.00, // quantity * price
            ]);

            $order->load('items.product');

            $result = $this->orderStockConversionService->convertOrderItemsToStockItems($order);

            expect($result)->toHaveCount(2);

            // Find each component in the result
            $flourItem = collect($result)->firstWhere('product_id', $flour->id);
            $sugarItem = collect($result)->firstWhere('product_id', $sugar->id);

            // Should sum up: cake (3 flour) + cookies (2*2 = 4 flour) = 7 flour total
            expect($flourItem['quantity'])->toBe(7.0);
            // Should sum up: cake (2 sugar) + cookies (2*1 = 2 sugar) = 4 sugar total
            expect($sugarItem['quantity'])->toBe(4.0);
        });
    });

    describe('removeStockForCompletedOrder', function () {
        it('calls stock service to remove stock for completed order', function () {
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 2,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $expectedStockItems = [
                ['product_id' => $consumableProduct->id, 'quantity' => 2]
            ];

            $this->stockService->shouldReceive('removeStock')
                ->once()
                ->with($expectedStockItems, MovementReason::ORDER, $order)
                ->andReturn(true);

            $result = $this->orderStockConversionService->removeStockForCompletedOrder($order);

            expect($result)->toBeTrue();
        });

        it('returns false and logs error when stock removal fails', function () {
            Log::shouldReceive('error')->once()
                ->with('Failed to remove stock for completed order', \Mockery::type('array'));

            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 2,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $this->stockService->shouldReceive('removeStock')
                ->once()
                ->andThrow(new \Exception('Stock removal failed'));

            $result = $this->orderStockConversionService->removeStockForCompletedOrder($order);

            expect($result)->toBeFalse();
        });

        it('returns true when order has no stock items to remove', function () {
            // Create order with only raw materials (which shouldn't be in stock operations)
            $rawProduct = Product::factory()->create([
                'type' => ProductType::RawMaterial,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $rawProduct->id,
                'quantity' => 2,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            // Should not call stockService at all
            $this->stockService->shouldNotReceive('removeStock');

            $result = $this->orderStockConversionService->removeStockForCompletedOrder($order);

            expect($result)->toBeTrue();
        });
    });

    describe('addStockForCancelledOrder', function () {
        it('calls stock service to add stock back for cancelled order', function () {
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 3,
                'price' => 15.00,
                'cost' => 7.50,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $expectedStockItems = [
                ['product_id' => $consumableProduct->id, 'quantity' => 3]
            ];

            $this->stockService->shouldReceive('addStock')
                ->once()
                ->with($expectedStockItems, MovementReason::ORDER_RETURN, $order)
                ->andReturn(true);

            $result = $this->orderStockConversionService->addStockForCancelledOrder($order);

            expect($result)->toBeTrue();
        });

        it('returns false and logs error when stock addition fails', function () {
            Log::shouldReceive('error')->once()
                ->with('Failed to add stock back for cancelled order', \Mockery::type('array'));

            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 2,
                'price' => 10.00,
                'cost' => 5.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $this->stockService->shouldReceive('addStock')
                ->once()
                ->andThrow(new \Exception('Stock addition failed'));

            $result = $this->orderStockConversionService->addStockForCancelledOrder($order);

            expect($result)->toBeFalse();
        });
    });

    describe('validateOrderStockAvailability', function () {
        it('calls stock service to validate stock availability', function () {
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 5,
                'price' => 20.00,
                'cost' => 10.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $expectedStockItems = [
                ['product_id' => $consumableProduct->id, 'quantity' => 5]
            ];

            $expectedResult = [
                [
                    'product_id' => $consumableProduct->id,
                    'product_name' => $consumableProduct->name,
                    'required_quantity' => 5,
                    'available_quantity' => 2,
                ]
            ];

            $this->stockService->shouldReceive('validateStockAvailability')
                ->once()
                ->with($expectedStockItems)
                ->andReturn($expectedResult);

            $result = $this->orderStockConversionService->validateOrderStockAvailability($order);

            expect($result)->toBe($expectedResult);
        });

        it('returns empty array when order has no stock items', function () {
            $order = Order::factory()->create();
            $order->load('items.product');

            $result = $this->orderStockConversionService->validateOrderStockAvailability($order);

            expect($result)->toBeEmpty();
        });
    });

    describe('getOrderStockRequirements', function () {
        it('returns stock requirements summary for order', function () {
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'name' => 'Test Product',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 4,
                'price' => 16.00,
                'cost' => 8.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $this->stockService->shouldReceive('getCurrentStock')
                ->once()
                ->with($consumableProduct->id)
                ->andReturn(6);

            $result = $this->orderStockConversionService->getOrderStockRequirements($order);

            expect($result)->toHaveCount(1);
            expect($result[0])->toBe([
                'product_id' => $consumableProduct->id,
                'product_name' => 'Test Product',
                'required_quantity' => 4,
                'current_stock' => 6.0,
                'sufficient' => true,
            ]);
        });

        it('marks insufficient stock correctly', function () {
            $consumableProduct = Product::factory()->create([
                'type' => ProductType::Consumable,
                'name' => 'Test Product',
                'category_id' => $this->category->id,
                'printer_id' => $this->printer->id,
            ]);

            $order = Order::factory()->create();
            $order->items()->create([
                'product_id' => $consumableProduct->id,
                'quantity' => 10,
                'price' => 40.00,
                'cost' => 20.00,
                'total' => 0.00, // quantity * price
            ]);

            $order->load('items.product');

            $this->stockService->shouldReceive('getCurrentStock')
                ->once()
                ->with($consumableProduct->id)
                ->andReturn(3);

            $result = $this->orderStockConversionService->getOrderStockRequirements($order);

            expect($result)->toHaveCount(1);
            expect($result[0]['sufficient'])->toBeFalse();
            expect($result[0]['required_quantity'])->toBe(10);
            expect($result[0]['current_stock'])->toBe(3.0);
        });

        it('returns empty array when order has no stock items', function () {
            $order = Order::factory()->create();
            $order->load('items.product');

            $result = $this->orderStockConversionService->getOrderStockRequirements($order);

            expect($result)->toBeEmpty();
        });
    });
});


