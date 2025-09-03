<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SettingsSeeder::class,
            // CategorySeeder::class,
            // PrinterSeeder::class,
            // SupplierSeeder::class,
            // RegionSeeder::class,
            // ExpenceTypeSeeder::class,
            // ProductSeeder::class,
            // ExpenseSeeder::class,
            // PurchaseInvoiceSeeder::class,
        ]);
    }
}
