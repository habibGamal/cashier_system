<?php

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\InventoryItem;
use App\Models\InventoryItemMovement;
use App\Models\InventoryItemMovementDaily;
use App\Services\InventoryDailyAggregationService;
use App\Enums\InventoryMovementOperation;
use App\Enums\MovementReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Unit\TestCase;

uses(Tests\Unit\TestCase::class);

describe('InventoryDailyAggregationService', function () {
    beforeEach(function () {
        $this->service = new InventoryDailyAggregationService();

        // Clear all data before each test
        InventoryItemMovementDaily::truncate();
        InventoryItemMovement::truncate();
        InventoryItem::truncate();
        Product::truncate();
    });

    describe('dayStatus', function () {
        it('returns null when no open day exists', function () {
            $result = $this->service->dayStatus();
            expect($result)->toBeNull();
        });

        it('returns the opened day date when there is an open day', function () {
            $product = Product::factory()->create();
            $testDate = Carbon::today();

            // Create an open day record
            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $testDate->toDateString(),
                'start_quantity' => 100.0,
                'incoming_quantity' => 0.0,
                'sales_quantity' => 0.0,
                'return_sales_quantity' => 0.0,
                'return_waste_quantity' => 0.0,
                'end_quantity' => 100.0,
                'closed_at' => null, // Open day
            ]);

            $result = $this->service->dayStatus();
            expect($result)->not()->toBeNull();
            expect($result->toDateString())->toBe($testDate->toDateString());
        });

        it('returns null when all days are closed', function () {
            $product = Product::factory()->create();
            $testDate = Carbon::today();

            // Create a closed day record
            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $testDate->toDateString(),
                'start_quantity' => 100.0,
                'incoming_quantity' => 0.0,
                'sales_quantity' => 0.0,
                'return_sales_quantity' => 0.0,
                'return_waste_quantity' => 0.0,
                'end_quantity' => 100.0,
                'closed_at' => now(), // Closed day
            ]);

            $result = $this->service->dayStatus();
            expect($result)->toBeNull();
        });

        it('returns the latest open day when multiple open days exist', function () {
            $product = Product::factory()->create();
            $yesterday = Carbon::yesterday();
            $today = Carbon::today();

            // Create two open day records
            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $yesterday->toDateString(),
                'start_quantity' => 100.0,
                'closed_at' => null,
            ]);

            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $today->toDateString(),
                'start_quantity' => 100.0,
                'closed_at' => null,
            ]);

            $result = $this->service->dayStatus();
            expect($result->toDateString())->toBe($today->toDateString());
        });
    });

    describe('openDay', function () {
        it('creates daily movement records for all products with current stock', function () {
            // Create products with inventory
            $products = Product::factory(3)->create();

            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 100.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 250.0]);
            InventoryItem::create(['product_id' => $products[2]->id, 'quantity' => 50.0]);

            // Call openDay (no parameters)
            $result = $this->service->openDay();

            expect($result)->toBe(3);

            // Verify records were created for today
            $dailyRecords = InventoryItemMovementDaily::where('date', Carbon::today())->get();
            expect($dailyRecords)->toHaveCount(3);

            // Verify start quantities are set correctly from current inventory
            foreach ($products as $index => $product) {
                $dailyRecord = $dailyRecords->where('product_id', $product->id)->first();
                expect($dailyRecord)->not()->toBeNull();
                expect($dailyRecord->start_quantity)->toEqual([100.0, 250.0, 50.0][$index]);
                expect($dailyRecord->incoming_quantity)->toEqual(0.0);
                expect($dailyRecord->sales_quantity)->toEqual(0.0);
                expect($dailyRecord->return_sales_quantity)->toEqual(0.0);
                expect($dailyRecord->return_waste_quantity)->toEqual(0.0);
                expect($dailyRecord->end_quantity)->toEqual([100.0, 250.0, 50.0][$index]);
                expect($dailyRecord->closed_at)->toBeNull();
            }
        });

        it('uses end_quantity from last day as start_quantity for new day', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            $yesterday = Carbon::yesterday();

            // Create a previous day record with different end_quantity
            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $yesterday->toDateString(),
                'start_quantity' => 100.0,
                'incoming_quantity' => 50.0,
                'sales_quantity' => 30.0,
                'return_sales_quantity' => 0.0,
                'return_waste_quantity' => 10.0,
                'end_quantity' => 110.0, // 100 + 50 - 30 - 10
                'closed_at' => now(),
            ]);

            $result = $this->service->openDay();

            expect($result)->toBe(1);

            $todayRecord = InventoryItemMovementDaily::where('date', Carbon::today())->first();
            expect($todayRecord)->not()->toBeNull();
            expect($todayRecord->start_quantity)->toEqual(110.0); // Should use yesterday's end_quantity
            expect($todayRecord->end_quantity)->toEqual(110.0); // Should initialize to start_quantity
        });

        it('reopens closed day by setting closed_at to null', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            $today = Carbon::today();

            // Create a closed day record for today
            InventoryItemMovementDaily::create([
                'product_id' => $product->id,
                'date' => $today->toDateString(),
                'start_quantity' => 100.0,
                'closed_at' => now(),
            ]);

            $result = $this->service->openDay();

            expect($result)->toBe(1); // Should return existing record count

            $todayRecord = InventoryItemMovementDaily::where('date', $today)->first();
            expect($todayRecord->closed_at)->toBeNull(); // Should be reopened
        });

        it('only creates records for products with inventory', function () {
            // Create products, some with inventory, some without
            $products = Product::factory(3)->create();

            // Only first two products have inventory
            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 100.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 250.0]);
            // Third product has no inventory

            $result = $this->service->openDay();

            expect($result)->toBe(2); // Only 2 records created

            $dailyRecords = InventoryItemMovementDaily::where('date', Carbon::today())->get();
            expect($dailyRecords)->toHaveCount(2);

            // Verify third product has no daily record
            $thirdProductRecord = $dailyRecords->where('product_id', $products[2]->id)->first();
            expect($thirdProductRecord)->toBeNull();
        });
    });

    describe('closeDay', function () {
        it('closes all open day records and sets end_quantity to current inventory', function () {
            $products = Product::factory(2)->create();

            // Create inventory items
            InventoryItem::create(['product_id' => $products[0]->id, 'quantity' => 150.0]);
            InventoryItem::create(['product_id' => $products[1]->id, 'quantity' => 300.0]);

            $today = Carbon::today();

            // Create open day records
            InventoryItemMovementDaily::create([
                'product_id' => $products[0]->id,
                'date' => $today->toDateString(),
                'start_quantity' => 100.0,
                'incoming_quantity' => 80.0,
                'sales_quantity' => 30.0,
                'closed_at' => null,
            ]);

            InventoryItemMovementDaily::create([
                'product_id' => $products[1]->id,
                'date' => $today->toDateString(),
                'start_quantity' => 200.0,
                'incoming_quantity' => 150.0,
                'sales_quantity' => 50.0,
                'closed_at' => null,
            ]);

            $result = $this->service->closeDay();

            expect($result)->toBe(2);

            // Verify all records are closed and end_quantity is set
            $closedRecords = InventoryItemMovementDaily::where('date', $today->toDateString())->get();

            foreach ($closedRecords as $record) {
                expect($record->closed_at)->not()->toBeNull();

                if ($record->product_id === $products[0]->id) {
                    expect($record->end_quantity)->toEqual(150.0); // Current inventory quantity
                } else {
                    expect($record->end_quantity)->toEqual(300.0); // Current inventory quantity
                }
            }
        });

        it('returns 0 when no open day exists', function () {
            $result = $this->service->closeDay();
            expect($result)->toBe(0);
        });
    });

    describe('bulkAggregateWithInsertSelect', function () {
        it('throws exception when no open day exists', function () {
            $product = Product::factory()->create();

            expect(fn() => $this->service->bulkAggregateWithInsertSelect([$product->id]))
                ->toThrow(\Exception::class, 'No open day found. Please open a day first.');
        });

        it('updates existing open day records with movement data', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            // Open a day first
            $this->service->openDay();

            $openDay = InventoryItemMovementDaily::whereNull('closed_at')->first();

            // Create some movements after the day was opened
            InventoryItemMovement::create([
                'product_id' => $product->id,
                'operation' => InventoryMovementOperation::IN,
                'quantity' => 50.0,
                'reason' => MovementReason::PURCHASE,
                'created_at' => $openDay->created_at->addMinutes(10),
            ]);

            InventoryItemMovement::create([
                'product_id' => $product->id,
                'operation' => InventoryMovementOperation::OUT,
                'quantity' => 30.0,
                'reason' => MovementReason::ORDER,
                'created_at' => $openDay->created_at->addMinutes(20),
            ]);

            InventoryItemMovement::create([
                'product_id' => $product->id,
                'operation' => InventoryMovementOperation::IN,
                'quantity' => 10.0,
                'reason' => MovementReason::ORDER_RETURN,
                'created_at' => $openDay->created_at->addMinutes(30),
            ]);

            // Call the aggregation method
            $this->service->bulkAggregateWithInsertSelect([$product->id]);

            // Verify the record was updated
            $updatedRecord = InventoryItemMovementDaily::where('product_id', $product->id)
                ->whereNull('closed_at')
                ->first();

            expect($updatedRecord->start_quantity)->toEqual(100.0); // Should remain unchanged
            expect($updatedRecord->incoming_quantity)->toEqual(50.0);
            expect($updatedRecord->sales_quantity)->toEqual(30.0);
            expect($updatedRecord->return_sales_quantity)->toEqual(10.0);
            expect($updatedRecord->return_waste_quantity)->toEqual(0.0);
        });

        it('creates records for products without existing daily records', function () {
            $existingProduct = Product::factory()->create();

            InventoryItem::create(['product_id' => $existingProduct->id, 'quantity' => 100.0]);

            // Open day for existing product only (this will create record for existing product)
            $this->service->openDay();


            $newProduct = Product::factory()->create(['type' => ProductType::RawMaterial]);


            $openDay = InventoryItemMovementDaily::whereNull('closed_at')->first();

            // Create movements for the new product
            InventoryItemMovement::create([
                'product_id' => $newProduct->id,
                'operation' => InventoryMovementOperation::IN,
                'quantity' => 25.0,
                'reason' => MovementReason::PURCHASE,
                'created_at' => $openDay->created_at->addMinutes(10),
            ]);

            // Call aggregation for both products
            $this->service->bulkAggregateWithInsertSelect([$existingProduct->id, $newProduct->id]);

            // Verify new record was created for new product
            $newProductRecord = InventoryItemMovementDaily::where('product_id', $newProduct->id)
                ->whereNull('closed_at')
                ->first();

            expect($newProductRecord)->not()->toBeNull();
            expect($newProductRecord->start_quantity)->toEqual(0.0); // New products start with 0
            expect($newProductRecord->incoming_quantity)->toEqual(25.0);
            expect($newProductRecord->date->toDateString())->toBe(Carbon::today()->toDateString());
            expect($newProductRecord->closed_at)->toBeNull();

            // Verify existing product record still exists
            $existingProductRecord = InventoryItemMovementDaily::where('product_id', $existingProduct->id)
                ->whereNull('closed_at')
                ->first();
            expect($existingProductRecord)->not()->toBeNull();
        });

        it('aggregates different movement types correctly', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            // Open a day
            $this->service->openDay();
            $openDay = InventoryItemMovementDaily::whereNull('closed_at')->first();

            // Create various types of movements
            $movements = [
                ['operation' => InventoryMovementOperation::IN, 'quantity' => 50.0, 'reason' => MovementReason::PURCHASE],
                ['operation' => InventoryMovementOperation::IN, 'quantity' => 20.0, 'reason' => MovementReason::PURCHASE],
                ['operation' => InventoryMovementOperation::OUT, 'quantity' => 30.0, 'reason' => MovementReason::ORDER],
                ['operation' => InventoryMovementOperation::OUT, 'quantity' => 15.0, 'reason' => MovementReason::ORDER],
                ['operation' => InventoryMovementOperation::IN, 'quantity' => 10.0, 'reason' => MovementReason::ORDER_RETURN],
                ['operation' => InventoryMovementOperation::OUT, 'quantity' => 5.0, 'reason' => MovementReason::WASTE],
                ['operation' => InventoryMovementOperation::OUT, 'quantity' => 8.0, 'reason' => MovementReason::PURCHASE_RETURN],
            ];

            foreach ($movements as $index => $movement) {
                InventoryItemMovement::create([
                    'product_id' => $product->id,
                    'operation' => $movement['operation'],
                    'quantity' => $movement['quantity'],
                    'reason' => $movement['reason'],
                    'created_at' => $openDay->created_at->addMinutes(($index + 1) * 5),
                ]);
            }

            $this->service->bulkAggregateWithInsertSelect([$product->id]);

            $record = InventoryItemMovementDaily::where('product_id', $product->id)
                ->whereNull('closed_at')
                ->first();

            expect($record->incoming_quantity)->toEqual(70.0); // 50 + 20
            expect($record->sales_quantity)->toEqual(45.0); // 30 + 15
            expect($record->return_sales_quantity)->toEqual(10.0); // 10
            expect($record->return_waste_quantity)->toEqual(13.0); // 5 + 8
        });

        it('ignores movements before the open day', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            // Create movements before opening the day
            InventoryItemMovement::insert([
                'product_id' => $product->id,
                'operation' => InventoryMovementOperation::IN,
                'quantity' => 999.0, // Large amount to detect if included
                'reason' => MovementReason::PURCHASE,
                'created_at' => Carbon::now()->subHour(),
            ]);

            // Open day
            $this->service->openDay();
            $openDay = InventoryItemMovementDaily::whereNull('closed_at')->first();

            // Create movements after opening the day
            InventoryItemMovement::create([
                'product_id' => $product->id,
                'operation' => InventoryMovementOperation::IN,
                'quantity' => 50.0,
                'reason' => MovementReason::PURCHASE,
                'created_at' => $openDay->created_at->addMinutes(10),
            ]);

            $this->service->bulkAggregateWithInsertSelect([$product->id]);

            $record = InventoryItemMovementDaily::where('product_id', $product->id)
                ->whereNull('closed_at')
                ->first();

            expect($record->incoming_quantity)->toEqual(50.0); // Should not include the 999.0
        });
    });

    describe('aggregateMultipleMovements', function () {
        it('calls bulkAggregateWithInsertSelect method', function () {
            $product = Product::factory()->create();
            InventoryItem::create(['product_id' => $product->id, 'quantity' => 100.0]);

            // Open a day first
            $this->service->openDay();

            // This should work without throwing an exception
            $this->service->aggregateMultipleMovements([$product->id], Carbon::today());

            // Verify the record exists (basic test that the method was called)
            $record = InventoryItemMovementDaily::where('product_id', $product->id)
                ->whereNull('closed_at')
                ->first();
            expect($record)->not()->toBeNull();
        });
    });
});
