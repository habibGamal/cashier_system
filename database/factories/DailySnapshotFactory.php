<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DailySnapshot;

class DailySnapshotFactory extends Factory
{
    protected $model = DailySnapshot::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'total_sales' => $this->faker->randomFloat(2, 100, 10000),
            'total_expenses' => $this->faker->randomFloat(2, 10, 5000),
            'profit' => $this->faker->randomFloat(2, 10, 5000),
        ];
    }
}
