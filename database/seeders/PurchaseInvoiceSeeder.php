<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $suppliers = Supplier::all();
        $products = Product::all();

        if ($users->isEmpty() || $suppliers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Create 15 purchase invoices
        for ($i = 1; $i <= 15; $i++) {
            $supplier = $suppliers->random();
            $user = $users->random();
            
            // Create purchase invoice
            $purchaseInvoice = PurchaseInvoice::create([
                'user_id' => $user->id,
                'supplier_id' => $supplier->id,
                'total' => 0, // Will be calculated later
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            // Create 2-6 items per invoice
            $itemCount = rand(2, 6);
            $invoiceTotal = 0;

            for ($j = 1; $j <= $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $price = $product->cost ?? $product->price;
                $total = $quantity * $price;

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ]);

                $invoiceTotal += $total;
            }

            // Update invoice total
            $purchaseInvoice->update(['total' => $invoiceTotal]);
        }
    }
}
