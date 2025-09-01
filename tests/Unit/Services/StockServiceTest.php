<?php

use App\Models\Product;
use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use App\Services\StockService;
use App\Services\InventoryDailyAggregationService;
use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use Tests\Unit\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(Tests\Unit\TestCase::class);

describe('StockService', function () {
    beforeEach(function () {
        $this->dailyAggregationService = \Mockery::mock(InventoryDailyAggregationService::class);
        $this->dailyAggregationService->shouldReceive('aggregateMultipleMovements')->andReturn([]);
        $this->stockService = new StockService($this->dailyAggregationService);
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('addStock', function () {
        it('can add stock for multiple items', function () {
            $products = Product::factory(3)->create();
            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 50.0],
                ['product_id' => $products[1]->id, 'quantity' => 75.0],
                ['product_id' => $products[2]->id, 'quantity' => 100.0]
            ];

            $result = $this->stockService->addStock($items, MovementReason::PURCHASE);

            expect($result)->toBeTrue();
            foreach ($items as $item) {
                $this->assertDatabaseHas('inventory_items', [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        });

        it('can add stock to existing inventory items', function () {
            $products = Product::factory(2)->create();

            // Create initial inventory
            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 50.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 30.0]);

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 25.0],
                ['product_id' => $products[1]->id, 'quantity' => 20.0]
            ];

            $result = $this->stockService->addStock($items, MovementReason::PURCHASE);

            expect($result)->toBeTrue();
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[0]->id,
                'quantity' => 75.0 // 50 + 25
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[1]->id,
                'quantity' => 50.0 // 30 + 20
            ]);
        });

        it('fails to add stock when products dont exist', function () {
            $items = [
                ['product_id' => 99999, 'quantity' => 50.0],
                ['product_id' => 99998, 'quantity' => 75.0]
            ];

            $result = $this->stockService->addStock($items, MovementReason::PURCHASE);

            expect($result)->toBeFalse();
            $this->assertDatabaseMissing('inventory_items', ['product_id' => 99999]);
            $this->assertDatabaseMissing('inventory_items', ['product_id' => 99998]);
        });
    });

    describe('removeStock', function () {
        it('can remove stock for multiple items with sufficient inventory', function () {
            $products = Product::factory(3)->create();

            // Create initial inventory
            foreach ($products as $product) {
                InventoryItem::create([
                    'product_id' => $product->id,
                    'quantity' => 100.0
                ]);
            }

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 20.0],
                ['product_id' => $products[1]->id, 'quantity' => 30.0],
                ['product_id' => $products[2]->id, 'quantity' => 40.0]
            ];

            $result = $this->stockService->removeStock($items, MovementReason::ORDER);

            expect($result)->toBeTrue();
            foreach ($items as $item) {
                $this->assertDatabaseHas('inventory_items', [
                    'product_id' => $item['product_id'],
                    'quantity' => 100.0 - $item['quantity']
                ]);
            }
        });

        it('fails to remove stock when insufficient inventory', function () {
            $products = Product::factory(2)->create();

            // Create initial inventory with limited stock
            InventoryItem::create([
                'product_id' => $products[0]->id,
                'quantity' => 10.0
            ]);
            InventoryItem::create([
                'product_id' => $products[1]->id,
                'quantity' => 15.0
            ]);

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 20.0], // More than available
                ['product_id' => $products[1]->id, 'quantity' => 10.0]  // Within available
            ];

            $result = $this->stockService->removeStock($items, MovementReason::ORDER);

            expect($result)->toBeFalse();
            // Quantities should remain unchanged due to transaction rollback
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[0]->id,
                'quantity' => 10.0
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[1]->id,
                'quantity' => 15.0
            ]);
        });

        it('fails to remove stock when products dont exist', function () {
            $items = [
                ['product_id' => 99999, 'quantity' => 50.0],
                ['product_id' => 99998, 'quantity' => 75.0]
            ];

            $result = $this->stockService->removeStock($items, MovementReason::ORDER);

            expect($result)->toBeFalse();
        });
    });

    describe('processMultipleItems', function () {
        it('can process multiple items for adding stock', function () {
            $products = Product::factory(3)->create();
            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 50.0],
                ['product_id' => $products[1]->id, 'quantity' => 75.0],
                ['product_id' => $products[2]->id, 'quantity' => 100.0]
            ];

            $result = $this->stockService->processMultipleItems($items, 'add', MovementReason::PURCHASE);

            expect($result)->toBeTrue();
            foreach ($items as $item) {
                $this->assertDatabaseHas('inventory_items', [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        });

        it('can process multiple items for removing stock', function () {
            $products = Product::factory(3)->create();

            // Create initial inventory
            foreach ($products as $product) {
                InventoryItem::create([
                    'product_id' => $product->id,
                    'quantity' => 100.0
                ]);
            }

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 20.0],
                ['product_id' => $products[1]->id, 'quantity' => 30.0],
                ['product_id' => $products[2]->id, 'quantity' => 40.0]
            ];

            $result = $this->stockService->processMultipleItems($items, 'remove', MovementReason::ORDER);

            expect($result)->toBeTrue();
            foreach ($items as $item) {
                $this->assertDatabaseHas('inventory_items', [
                    'product_id' => $item['product_id'],
                    'quantity' => 100.0 - $item['quantity']
                ]);
            }
        });

        it('fails to process multiple items when products dont exist', function () {
            $items = [
                ['product_id' => 99999, 'quantity' => 50.0],
                ['product_id' => 99998, 'quantity' => 75.0]
            ];

            $result = $this->stockService->processMultipleItems($items, 'add', MovementReason::PURCHASE);

            expect($result)->toBeFalse();
            $this->assertDatabaseMissing('inventory_items', [
                'product_id' => 99999
            ]);
            $this->assertDatabaseMissing('inventory_items', [
                'product_id' => 99998
            ]);
        });

        it('fails to process multiple items when insufficient stock for removal', function () {
            $products = Product::factory(2)->create();

            // Create initial inventory with limited stock
            InventoryItem::create([
                'product_id' => $products[0]->id,
                'quantity' => 10.0
            ]);
            InventoryItem::create([
                'product_id' => $products[1]->id,
                'quantity' => 15.0
            ]);

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 20.0], // More than available
                ['product_id' => $products[1]->id, 'quantity' => 10.0]  // Within available
            ];

            $result = $this->stockService->processMultipleItems($items, 'remove', MovementReason::ORDER);

            expect($result)->toBeFalse();
            // Quantities should remain unchanged due to transaction rollback
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[0]->id,
                'quantity' => 10.0
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[1]->id,
                'quantity' => 15.0
            ]);
        });

        it('can handle mixed existing and new inventory items in bulk add', function () {
            $products = Product::factory(3)->create();

            // Create inventory for first product only
            InventoryItem::create([
                'product_id' => $products[0]->id,
                'quantity' => 50.0
            ]);

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 30.0], // Update existing
                ['product_id' => $products[1]->id, 'quantity' => 40.0], // Create new
                ['product_id' => $products[2]->id, 'quantity' => 60.0], // Create new
            ];

            $result = $this->stockService->processMultipleItems($items, 'add', MovementReason::PURCHASE);

            expect($result)->toBeTrue();
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[0]->id,
                'quantity' => 80.0 // 50 + 30
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[1]->id,
                'quantity' => 40.0
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_id' => $products[2]->id,
                'quantity' => 60.0
            ]);
        });
    });

    describe('getCurrentStock', function () {
        it('can get current stock for existing product', function () {
            $product = Product::factory()->create();
            $quantity = 75.5;

            InventoryItem::create([
                'product_id' => $product->id,
                'quantity' => $quantity
            ]);

            $currentStock = $this->stockService->getCurrentStock($product->id);

            expect($currentStock)->toBe($quantity);
        });

        it('returns zero stock for product without inventory', function () {
            $product = Product::factory()->create();

            $currentStock = $this->stockService->getCurrentStock($product->id);

            expect($currentStock)->toBe(0.0);
        });
    });

    describe('hasSufficientStock', function () {
        it('can check if product has sufficient stock', function () {
            $product = Product::factory()->create();
            $availableQuantity = 100.0;

            InventoryItem::create([
                'product_id' => $product->id,
                'quantity' => $availableQuantity
            ]);

            expect($this->stockService->hasSufficientStock($product->id, 50.0))->toBeTrue()
                ->and($this->stockService->hasSufficientStock($product->id, 100.0))->toBeTrue()
                ->and($this->stockService->hasSufficientStock($product->id, 150.0))->toBeFalse();
        });
    });

    describe('validateStockAvailability', function () {
        it('can validate stock availability for multiple items', function () {
            $products = Product::factory(3)->create();

            // Create inventory with different quantities
            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 100.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 50.0]);
            // No inventory for products[2]

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 80.0],  // Sufficient
                ['product_id' => $products[1]->id, 'quantity' => 60.0],  // Insufficient
                ['product_id' => $products[2]->id, 'quantity' => 20.0],  // No inventory
            ];

            $insufficientItems = $this->stockService->validateStockAvailability($items);

            expect($insufficientItems)->toHaveCount(2);

            // Check insufficient item details
            expect($insufficientItems[0]['product_id'])->toBe($products[1]->id)
                ->and($insufficientItems[0]['product_name'])->toBe($products[1]->name)
                ->and($insufficientItems[0]['required_quantity'])->toEqual(60.0)
                ->and($insufficientItems[0]['available_quantity'])->toEqual(50.0);

            expect($insufficientItems[1]['product_id'])->toBe($products[2]->id)
                ->and($insufficientItems[1]['product_name'])->toBe($products[2]->name)
                ->and($insufficientItems[1]['required_quantity'])->toEqual(20.0)
                ->and($insufficientItems[1]['available_quantity'])->toEqual(0.0);
        });

        it('returns empty array when all items have sufficient stock', function () {
            $products = Product::factory(2)->create();

            // Create inventory with sufficient quantities
            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 100.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 200.0]);

            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 50.0],
                ['product_id' => $products[1]->id, 'quantity' => 150.0],
            ];

            $insufficientItems = $this->stockService->validateStockAvailability($items);

            expect($insufficientItems)->toBeEmpty();
        });
    });

    describe('movement tracking', function () {
        it('records movements when processing multiple items', function () {
            $products = Product::factory(2)->create();
            $items = [
                ['product_id' => $products[0]->id, 'quantity' => 50.0],
                ['product_id' => $products[1]->id, 'quantity' => 75.0]
            ];

            $result = $this->stockService->processMultipleItems($items, 'add', MovementReason::PURCHASE);

            expect($result)->toBeTrue();

            // Check that movements were recorded
            $movements = InventoryItemMovement::all();
            expect($movements)->toHaveCount(2);

            $movement1 = $movements->where('product_id', $products[0]->id)->first();
            expect($movement1->operation)->toBe(InventoryMovementOperation::IN)
                ->and($movement1->quantity)->toEqual(50.0)
                ->and($movement1->reason)->toBe(MovementReason::PURCHASE);

            $movement2 = $movements->where('product_id', $products[1]->id)->first();
            expect($movement2->operation)->toBe(InventoryMovementOperation::IN)
                ->and($movement2->quantity)->toEqual(75.0)
                ->and($movement2->reason)->toBe(MovementReason::PURCHASE);
        });

        it('records movements for stock removal', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            $items = [['product_id' => $product->id, 'quantity' => 30.0]];
            $result = $this->stockService->processMultipleItems($items, 'remove', MovementReason::ORDER);

            expect($result)->toBeTrue();

            $movement = InventoryItemMovement::first();
            expect($movement->operation)->toBe(InventoryMovementOperation::OUT)
                ->and($movement->quantity)->toEqual(30.0)
                ->and($movement->reason)->toBe(MovementReason::ORDER);
        });
    });

    describe('getDailyMovementSummary', function () {
        it('can get daily movement summary for a product', function () {
            $product = Product::factory()->create();
            $startDate = \Carbon\Carbon::parse('2024-01-01');
            $endDate = \Carbon\Carbon::parse('2024-01-31');

            $expectedSummary = [
                ['date' => '2024-01-01', 'incoming_quantity' => 100, 'sales_quantity' => 50],
                ['date' => '2024-01-02', 'incoming_quantity' => 0, 'sales_quantity' => 30],
            ];

            $this->dailyAggregationService->shouldReceive('getDailySummary')
                ->with($product->id, $startDate, $endDate)
                ->andReturn($expectedSummary);

            $summary = $this->stockService->getDailyMovementSummary($product->id, $startDate, $endDate);

            expect($summary)->toBe($expectedSummary);
        });
    });

});
