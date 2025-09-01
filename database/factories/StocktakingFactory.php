<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Stocktaking;

class StocktakingFactory extends Factory
{
    protected $model = Stocktaking::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
