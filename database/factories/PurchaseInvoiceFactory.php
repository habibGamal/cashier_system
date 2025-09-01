<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PurchaseInvoice;

class PurchaseInvoiceFactory extends Factory
{
    protected $model = PurchaseInvoice::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'supplier_id' => 1,
            'total' => $this->faker->randomFloat(2, 10, 500),
        ];
    }
}
