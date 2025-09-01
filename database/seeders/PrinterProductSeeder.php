<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrinterProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = \App\Models\Product::all();
        $printers = \App\Models\Printer::all();

        if ($products->isEmpty() || $printers->isEmpty()) {
            $this->command->info('No products or printers found. Skipping printer-product relationships.');
            return;
        }

        // Attach random printers to each product (1-3 printers per product)
        foreach ($products as $product) {
            $randomPrinters = $printers->random(rand(1, min(3, $printers->count())));
            $product->printers()->attach($randomPrinters->pluck('id'));
        }

        $this->command->info('Printer-product relationships created successfully.');
    }
}
