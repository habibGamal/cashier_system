<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PurchaseInvoiceItem;

class PurchaseInvoiceItemFactory extends Factory
{
    protected $model = PurchaseInvoiceItem::class;

    public function definition(): array
    {
        return [
            'purchase_invoice_id' => 1,
            'product_id' => 1,
            'quantity' => $this->faker->numberBetween(1, 100),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'total' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}
