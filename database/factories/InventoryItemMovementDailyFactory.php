<?php

namespace Database\Factories;

use App\Models\InventoryItemMovementDaily;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItemMovementDaily>
 */
class InventoryItemMovementDailyFactory extends Factory
{
    protected $model = InventoryItemMovementDaily::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'start_quantity' => $this->faker->randomFloat(2, 0, 1000),
            'incoming_quantity' => $this->faker->randomFloat(2, 0, 500),
            'sales_quantity' => $this->faker->randomFloat(2, 0, 300),
            'return_waste_quantity' => $this->faker->randomFloat(2, 0, 50),
        ];
    }

    /**
     * Configure the factory for a specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Configure the factory for a specific date
     */
    public function forDate(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Configure the factory for today
     */
    public function today(): static
    {
        return $this->forDate(Carbon::today());
    }

    /**
     * Configure with high sales
     */
    public function highSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'sales_quantity' => $this->faker->randomFloat(2, 500, 1000),
            'incoming_quantity' => $this->faker->randomFloat(2, 600, 1200),
            'start_quantity' => $this->faker->randomFloat(2, 200, 800),
        ]);
    }

    /**
     * Configure with no activity
     */
    public function noActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'incoming_quantity' => 0,
            'sales_quantity' => 0,
            'return_waste_quantity' => 0,
        ]);
    }

    /**
     * Configure with waste/returns
     */
    public function withWaste(): static
    {
        return $this->state(fn (array $attributes) => [
            'return_waste_quantity' => $this->faker->randomFloat(2, 50, 200),
        ]);
    }
}
