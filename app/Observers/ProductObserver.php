<?php

namespace App\Observers;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\InventoryItem;

class ProductObserver
{
    /**
     * Handle the Product "creating" event.
     */
    public function creating(Product $product): void
    {
        // Auto-assign product_ref if not provided
        if (empty($product->product_ref)) {
            $product->product_ref = $this->generateProductRef();
        }
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Create inventory item for the product with initial quantity of 0
        if ($product->type !== ProductType::Manufactured) {
            InventoryItem::create([
                'product_id' => $product->id,
                'quantity' => 0,
            ]);
        }
    }

    /**
     * Handle the Product "saving" event.
     */
    public function saving(Product $product): void
    {
        // Auto-assign product_ref if not provided (fallback for mass assignment)
        if (empty($product->product_ref)) {
            $product->product_ref = $this->generateProductRef();
        }

        // set price of raw material = its cost
        if ($product->type === ProductType::RawMaterial) {
            $product->price = $product->cost;
        }
    }

    /**
     * Handle the Product "saving" event.
     */
    public function saved(Product $product): void
    {
        // Auto-assign product_ref if not provided (fallback for mass assignment)
        if ($product->type !== ProductType::Manufactured) {
            $product->load('componentOf');
            $product->componentOf->each(function ($component) {
                $component->updateManufacturedCost();
            });
        }
    }

    /**
     * Generate a unique product reference
     */
    private function generateProductRef(): string
    {
        do {
            // Generate a product reference like "P000001", "P000002", etc.
            $lastProduct = Product::whereNotNull('product_ref')
                ->where('product_ref', 'LIKE', 'P%')
                ->orderByRaw('CAST(SUBSTRING(product_ref, 2) AS UNSIGNED) DESC')
                ->first();

            if ($lastProduct && preg_match('/^P(\d+)$/', $lastProduct->product_ref, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }

            $productRef = 'P' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Check if this reference already exists
            $exists = Product::where('product_ref', $productRef)->exists();

        } while ($exists);

        return $productRef;
    }
}
