<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Printer;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'cost' => $this->faker->randomFloat(2, 1, 50),
            'min_stock' => $this->faker->randomFloat(2, 5, 50),
            'type' => 'manufactured',
            'unit' => 'piece',
            'legacy' => $this->faker->boolean,
        ];
    }

    /**
     * Attach a printer to the product after creation.
     */
    public function withPrinter(): static
    {
        return $this->afterCreating(function (Product $product) {
            $printer = Printer::factory()->create();
            $product->printers()->attach($printer->id);
        });
    }
}
