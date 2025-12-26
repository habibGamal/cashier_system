<?php

namespace App\Filament\Components\Forms;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ProductSelector extends Select
{
    protected $additionalPropsCallback;

    public function additionalProps(callable $additional): static
    {
        $this->additionalPropsCallback = $additional;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('إضافة منتج')
            ->placeholder('اختر منتج لإضافته...')
            ->searchable()
            ->allowHtml()
            // ->extraAttributes([
            //     'x-on:change' => <<<JS

            //         const items = [];
            //         items.push({
            //             product_id: \$event.target.value,
            //             product_name: \$event.target.selectedOptions[0].text,
            //             quantity: 1,
            //             price: \$event.target.dataset.price,
            //             total: \$event.target.dataset.price
            //         });
            //         // \$wire.data.items.set(items);
            //         \$wire.set('data.items', items,false);
            //         console.log(\$wire.data);
            //     JS
            // ])
            ->options(function () {
                $products = Product::whereIn('type', [
                    ProductType::RawMaterial,
                    ProductType::Consumable,
                ])
                    ->with('category')
                    ->get();

                return $products->mapWithKeys(function ($product) {
                    $price = $product->cost ?? $product->price;
                    $categoryName = $product->category ? $product->category->name : 'بدون فئة';

                    $label = $product->name.' - '.format_money($price).' ('.$categoryName.')';

                    return [$product->id => $label];
                });
            })
            ->live()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if (! $state) {
                    return;
                }

                $product = Product::find($state);
                if (! $product) {
                    return;
                }

                $currentItems = $get('items') ?? [];
                // Check if product already exists in the list
                $existingProductIds = collect($currentItems)->pluck('product_id')->filter()->toArray();

                if (in_array($product->id, $existingProductIds)) {
                    Notification::make()
                        ->title('تحذير')
                        ->body('هذا المنتج موجود بالفعل في القائمة')
                        ->warning()
                        ->send();

                    // Reset the select
                    $set('product_selector', null);

                    return;
                }

                // Prepare new item data
                $price = $product->cost ?? $product->price;
                $quantity = 1;
                $total = $quantity * $price;

                $newItem = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ];

                // Add additional props if callback is provided
                if ($this->additionalPropsCallback) {
                    $additionalProps = ($this->additionalPropsCallback)($product);
                    $newItem = array_merge($newItem, $additionalProps);
                }

                // Add the new item
                $currentItems[] = $newItem;
                $set('items', $currentItems);

                // Recalculate total
                $invoiceTotal = 0;
                foreach ($currentItems as $item) {
                    $invoiceTotal += $item['total'] ?? 0;
                }
                $set('total', $invoiceTotal);

                // Reset the select
                $set('product_selector', null);

            })
            ->dehydrated(false); // Don't save this field's value
    }

    public static function make(?string $name = 'product_selector'): static
    {
        return parent::make($name);
    }
}
