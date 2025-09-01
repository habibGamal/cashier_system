<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\StocktakingItem;

class StocktakingItemFactory extends Factory
{
    protected $model = StocktakingItem::class;

    public function definition(): array
    {
        return [
            'stocktaking_id' => 1,
            'product_id' => 1,
            'quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
