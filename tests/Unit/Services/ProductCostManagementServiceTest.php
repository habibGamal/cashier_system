<?php

namespace Tests\Unit\Services;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\InventoryItem;
use App\Services\ProductCostManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductCostManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductCostManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductCostManagementService();
    }

    public function test_updates_raw_material_cost_with_average_strategy()
    {
        // Create a raw material product
        $product = Product::factory()->create([
            'type' => ProductType::RawMaterial,
            'cost' => 10.0,
        ]);

        // Create inventory item with current stock
        InventoryItem::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 100,
        ]);

        // Purchase items data
        $purchaseItems = collect([
            [
                'product_id' => $product->id,
                'quantity' => 50,
                'price' => 15.0,
            ]
        ]);

        // Update costs
        $result = $this->service->updateProductCostsWithAverage($purchaseItems);

        // Assert success
        $this->assertTrue($result);

        // Calculate expected cost: (10 * 100 + 15 * 50) / 150 = 11.67
        $expectedCost = (10.0 * 100 + 15.0 * 50) / 150;

        // Refresh product and check new cost
        $product->refresh();
        $this->assertEqualsWithDelta($expectedCost, $product->cost, 0.01);
    }

    public function test_updates_consumable_cost_with_average_strategy()
    {
        // Create a consumable product
        $product = Product::factory()->create([
            'type' => ProductType::Consumable,
            'cost' => 5.0,
        ]);

        // Create inventory item with current stock
        InventoryItem::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 200,
        ]);

        // Purchase items data
        $purchaseItems = collect([
            [
                'product_id' => $product->id,
                'quantity' => 100,
                'price' => 8.0,
            ]
        ]);

        // Update costs
        $this->service->updateProductCostsWithAverage($purchaseItems);

        // Calculate expected cost: (5 * 200 + 8 * 100) / 300 = 6
        $expectedCost = (5.0 * 200 + 8.0 * 100) / 300;

        // Refresh product and check new cost
        $product->refresh();
        $this->assertEqualsWithDelta($expectedCost, $product->cost, 0.01);
    }

    public function test_skips_manufactured_products()
    {
        // Create a manufactured product
        $product = Product::factory()->create([
            'type' => ProductType::Manufactured,
            'cost' => 20.0,
        ]);

        // Create inventory item
        InventoryItem::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 50,
        ]);

        // Purchase items data
        $purchaseItems = collect([
            [
                'product_id' => $product->id,
                'quantity' => 25,
                'price' => 30.0,
            ]
        ]);

        // Update costs
        $this->service->updateProductCostsWithAverage($purchaseItems);

        // Refresh product and check cost remains unchanged
        $product->refresh();
        $this->assertEquals(20.0, $product->cost);
    }

    public function test_calculate_new_average_cost_preview()
    {
        // Create a product
        $product = Product::factory()->create([
            'type' => ProductType::RawMaterial,
            'cost' => 12.0,
        ]);

        // Create inventory item
        InventoryItem::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 80,
        ]);

        // Calculate what the new cost would be
        $newCost = $this->service->calculateNewAverageCost($product->id, 40, 18.0);

        // Expected: (12 * 80 + 18 * 40) / 120 = 14
        $expected = (12.0 * 80 + 18.0 * 40) / 120;

        $this->assertEqualsWithDelta($expected, $newCost, 0.01);
    }

    public function test_get_cost_impact_summary()
    {
        // Create products
        $product1 = Product::factory()->create([
            'name' => 'Product 1',
            'type' => ProductType::RawMaterial,
            'cost' => 10.0,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Product 2',
            'type' => ProductType::Consumable,
            'cost' => 5.0,
        ]);

        // Create inventory items
        InventoryItem::factory()->create([
            'product_id' => $product1->id,
            'current_stock' => 100,
        ]);

        InventoryItem::factory()->create([
            'product_id' => $product2->id,
            'current_stock' => 200,
        ]);

        // Purchase items
        $purchaseItems = collect([
            [
                'product_id' => $product1->id,
                'quantity' => 50,
                'price' => 15.0,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 100,
                'price' => 8.0,
            ],
        ]);

        $summary = $this->service->getCostImpactSummary($purchaseItems);

        $this->assertCount(2, $summary);

        // Check first product
        $this->assertEquals($product1->id, $summary[0]['product_id']);
        $this->assertEquals('Product 1', $summary[0]['product_name']);
        $this->assertEquals(10.0, $summary[0]['current_cost']);

        // Check second product
        $this->assertEquals($product2->id, $summary[1]['product_id']);
        $this->assertEquals('Product 2', $summary[1]['product_name']);
        $this->assertEquals(5.0, $summary[1]['current_cost']);
    }

    public function test_handles_zero_current_stock()
    {
        // Create a product with no current stock
        $product = Product::factory()->create([
            'type' => ProductType::RawMaterial,
            'cost' => 0,
        ]);

        // No inventory item created (or with zero stock)
        InventoryItem::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 0,
        ]);

        // Purchase items data
        $purchaseItems = collect([
            [
                'product_id' => $product->id,
                'quantity' => 25,
                'price' => 20.0,
            ]
        ]);

        // Update costs
        $this->service->updateProductCostsWithAverage($purchaseItems);

        // With zero stock, new cost should be the purchase price
        $product->refresh();
        $this->assertEquals(20.0, $product->cost);
    }
}
