<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'driver_id' => null,
            'user_id' => User::factory(),
            'shift_id' => Shift::factory(),
            'status' => OrderStatus::PROCESSING->value,
            'type' => OrderType::DINE_IN->value,
            'sub_total' => $this->faker->randomFloat(2, 10, 200),
            'tax' => $this->faker->randomFloat(2, 0, 20),
            'service' => $this->faker->randomFloat(2, 0, 20),
            'discount' => $this->faker->randomFloat(2, 0, 20),
            'temp_discount_percent' => $this->faker->randomFloat(2, 0, 100),
            'total' => $this->faker->randomFloat(2, 10, 300),
            'profit' => $this->faker->randomFloat(2, 1, 100),
            'payment_status' => PaymentStatus::PENDING->value,
            'dine_table_number' => $this->faker->optional()->randomNumber(),
            'kitchen_notes' => $this->faker->optional()->sentence(),
            'order_notes' => $this->faker->optional()->sentence(),
            'order_number' => $this->faker->unique()->randomNumber(),
        ];
    }
}
