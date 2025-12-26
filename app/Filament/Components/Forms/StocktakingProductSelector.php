<?php

namespace App\Filament\Components\Forms;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class StocktakingProductSelector extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('إضافة منتج للجرد')
            ->placeholder('اختر منتج لإضافته...')
            ->searchable()
            ->allowHtml()
            ->options(function () {
                $products = Product::whereIn('type', [
                    ProductType::RawMaterial,
                    ProductType::Consumable,
                ])
                    ->with(['category', 'inventoryItem'])
                    ->get();

                return $products->mapWithKeys(function ($product) {
                    $price = $product->cost ?? $product->price;
                    $categoryName = $product->category ? $product->category->name : 'بدون فئة';
                    $currentStock = $product->inventoryItem ? $product->inventoryItem->quantity : 0;

                    $label = $product->name.' - '.format_money($price).' ('.$categoryName.')';

                    return [$product->id => $label];
                });
            })
            ->live()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if (! $state) {
                    return;
                }

                $product = Product::with('inventoryItem')->find($state);
                if (! $product) {
                    return;
                }

                $currentItems = $get('items') ?? [];

                // Check if product already exists in the list
                $existingProductIds = collect($currentItems)->pluck('product_id')->filter()->toArray();

                if (in_array($product->id, $existingProductIds)) {
                    Notification::make()
                        ->title('تحذير')
                        ->body('هذا المنتج موجود بالفعل في قائمة الجرد')
                        ->warning()
                        ->send();

                    // Reset the select
                    $set('stocktaking_product_selector', null);

                    return;
                }

                // Prepare new item data for stocktaking
                $price = $product->cost ?? $product->price ?? 0;
                $stockQuantity = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                $realQuantity = 0; // Default to zero
                $total = ($realQuantity - $stockQuantity) * $price; // Will be 0 initially

                $newItem = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'stock_quantity' => $stockQuantity,
                    'real_quantity' => $realQuantity,
                    'price' => $price,
                    'total' => $total,
                ];

                // Add the new item
                $currentItems[] = $newItem;
                $set('items', $currentItems);

                // Recalculate stocktaking total
                $stocktakingTotal = 0;
                foreach ($currentItems as $item) {
                    $stockQty = (float) ($item['stock_quantity'] ?? 0);
                    $realQty = (float) ($item['real_quantity'] ?? 0);
                    $price = (float) ($item['price'] ?? 0);
                    $stocktakingTotal += ($realQty - $stockQty) * $price;
                }
                $set('total', $stocktakingTotal);

                // Reset the select
                $set('stocktaking_product_selector', null);

                // Show success notification
                Notification::make()
                    ->title('تم إضافة المنتج للجرد')
                    ->body("تم إضافة {$product->name} بنجاح - المخزون الحالي: {$stockQuantity}")
                    ->success()
                    ->send();
            })
            ->dehydrated(false); // Don't save this field's value
    }

    public static function make(?string $name = 'stocktaking_product_selector'): static
    {
        return parent::make($name);
    }
}
