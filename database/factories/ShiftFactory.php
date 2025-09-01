<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-7 days', 'now');
        $endAt = fake()->boolean(70) ? fake()->dateTimeBetween($startAt, 'now') : null;
        $startCash = fake()->randomFloat(2, 100, 1000);
        $endCash = $endAt ? fake()->randomFloat(2, $startCash, $startCash + 2000) : null;
        $realCash = $endAt ? fake()->randomFloat(2, $endCash - 100, $endCash + 100) : null;

        return [
            'user_id' => User::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'start_cash' => $startCash,
            'end_cash' => $endCash,
            'losses_amount' => $endAt ? fake()->randomFloat(2, 0, 50) : null,
            'real_cash' => $realCash,
            'has_deficit' => $endAt && $realCash ? $realCash < $endCash : false,
            'closed' => $endAt ? fake()->boolean(90) : false,
        ];
    }

    /**
     * Indicate that the shift is currently active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_at' => null,
            'end_cash' => null,
            'real_cash' => null,
            'closed' => false,
        ]);
    }

    /**
     * Indicate that the shift is closed.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $endAt = fake()->dateTimeBetween($attributes['start_at'], 'now');
            $endCash = fake()->randomFloat(2, $attributes['start_cash'], $attributes['start_cash'] + 2000);
            $realCash = fake()->randomFloat(2, $endCash - 100, $endCash + 100);

            return [
                'end_at' => $endAt,
                'end_cash' => $endCash,
                'real_cash' => $realCash,
                'has_deficit' => $realCash < $endCash,
                'closed' => true,
            ];
        });
    }

    /**
     * Indicate that the shift has a deficit.
     */
    public function withDeficit(): static
    {
        return $this->state(function (array $attributes) {
            $endCash = $attributes['end_cash'] ?? fake()->randomFloat(2, 500, 1500);
            $realCash = fake()->randomFloat(2, 0, $endCash - 50); // Always less than end_cash

            return [
                'end_cash' => $endCash,
                'real_cash' => $realCash,
                'has_deficit' => true,
            ];
        });
    }
}
