<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Shift;
use App\Enums\PaymentMethod;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 200),
            'method' => $this->faker->randomElement(PaymentMethod::cases())->value,
            'shift_id' => Shift::factory(),
        ];
    }
}
