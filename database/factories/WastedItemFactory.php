<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WastedItem;

class WastedItemFactory extends Factory
{
    protected $model = WastedItem::class;

    public function definition(): array
    {
        return [
            'waste_id' => 1,
            'product_id' => 1,
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
