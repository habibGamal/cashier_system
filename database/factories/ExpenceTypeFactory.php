<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ExpenceType;

class ExpenceTypeFactory extends Factory
{
    protected $model = ExpenceType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
