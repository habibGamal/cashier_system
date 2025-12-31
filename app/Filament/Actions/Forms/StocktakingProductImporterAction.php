<?php

namespace App\Filament\Actions\Forms;

use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Category;
use App\Models\Product;
use App\Models\InventoryItem;
use App\Enums\ProductType;

class StocktakingProductImporterAction extends Action
{
    protected $products;

    public static function getDefaultName(): ?string
    {
        return 'stocktakingProductImporter';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = collect();
        $this->label('استيراد المنتجات')
            ->icon('heroicon-m-plus-circle')
            ->color('success')
            ->form([
                Select::make('selected_collection')
                    ->label('المنتجات المختارة')
                    ->options(function () {
                        return Product::whereIn('type', [
                            ProductType::RawMaterial,
                            ProductType::Consumable,
                        ])->get()->pluck('name', 'id');
                    })
                    ->multiple(),

                Select::make('category_filter')
                    ->label('فلترة حسب الفئة')
                    ->placeholder('جميع الفئات')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) => $set('selected_products', [])),

                CheckboxList::make('selected_products')
                    ->label('اختر المنتجات للاستيراد')
                    ->options(function (Get $get) {
                        $query = Product::query()->whereIn('type', [
                            ProductType::RawMaterial,
                            ProductType::Consumable,
                        ])->with(['category', 'inventoryItem']);

                        // Filter by category if selected
                        if ($categoryId = $get('category_filter')) {
                            $query->where('category_id', $categoryId);
                        }

                        // Filter by search term if provided
                        if ($search = $get('search_filter')) {
                            $query->where('name', 'like', '%' . $search . '%');
                        }

                        $this->products = $query->get();
                        return $this->products->mapWithKeys(function ($product) {
                            $price = $product->cost ?? $product->price;
                            $categoryName = $product->category ? $product->category->name : 'بدون فئة';
                            $currentStock = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                            return [
                                $product->id => $product->name . ' - ' . $price . ' ج.م' . ' (' . $categoryName . ') - المخزون: ' . $currentStock
                            ];
                        });
                    })
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // When selected_products is updated, also update selected_collection to match
                        $currentCollection = $get('selected_collection') ?? [];
                        $set('selected_collection', collect([...$currentCollection, ...$state])->unique()->values()->all());
                    })
                    ->descriptions(function (Get $get) {
                        return $this->products->mapWithKeys(function ($product) {
                            $cost = $product->cost ?? 0;
                            $price = $product->price ?? 0;
                            $currentStock = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                            $description = "سعر التكلفة: {$cost} ج.م | سعر البيع: {$price} ج.م | المخزون الحالي: {$currentStock}";
                            if ($product->unit) {
                                $description .= " | الوحدة: {$product->unit}";
                            }
                            return [$product->id => $description];
                        });
                    })
            ])
            ->action(function (array $data, Set $set, Get $get) {
                $selectedProducts = $data['selected_collection'] ?? [];
                $products = Product::with('inventoryItem')->whereIn('id', $selectedProducts)->get();
                $currentItems = $get('items') ?? [];

                // Get existing product IDs to avoid duplicates
                $existingProductIds = collect($currentItems)->pluck('product_id')->filter()->toArray();

                $addedCount = 0;
                $skippedCount = 0;

                foreach ($selectedProducts as $productId) {
                    // Skip if product already exists in the list
                    if (in_array($productId, $existingProductIds)) {
                        $skippedCount++;
                        continue;
                    }

                    $product = $products->where('id', $productId)->first();
                    if ($product) {
                        $price = $product->cost ?? $product->price ?? 0;
                        $stockQuantity = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                        $realQuantity = 0; // Default to zero
                        $total = ($realQuantity - $stockQuantity) * $price; // Will be 0 initially

                        $currentItems[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'stock_quantity' => $stockQuantity,
                            'real_quantity' => $realQuantity,
                            'price' => $price,
                            'total' => $total,
                        ];
                        $addedCount++;
                    }
                }

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

                // Show notification
                $message = "تم إضافة {$addedCount} منتج بنجاح";
                if ($skippedCount > 0) {
                    $message .= " وتم تجاهل {$skippedCount} منتج موجود مسبقاً";
                }

                Notification::make()
                    ->title('تم استيراد المنتجات')
                    ->body($message)
                    ->success()
                    ->send();
            })
            ->modalHeading('استيراد المنتجات للجرد')
            ->modalSubheading('اختر المنتجات التي تريد إضافتها إلى الجرد. سيتم استيراد الكميات الحالية من المخزون.')
            ->modalWidth('2xl')
            ->slideOver();
    }
}
