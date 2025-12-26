<?php

namespace App\Filament\Actions\Forms;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class LowStockImporterAction extends Action
{
    protected $products;

    protected $additional;

    public function additionalProps(callable $additional): static
    {
        $this->additional = $additional;

        return $this;
    }

    public static function getDefaultName(): ?string
    {
        return 'lowStockImporter';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = collect();
        $this->label('استيراد المنتجات حسب معدل الشراء')
            ->icon('heroicon-m-exclamation-triangle')
            ->color('warning')
            ->form([
                Select::make('category_filter')
                    ->label('فلترة حسب الفئة')
                    ->placeholder('جميع الفئات')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('selected_products', [])),

                CheckboxList::make('selected_products')
                    ->label('المنتجات منخفضة المخزون')
                    ->helperText('هذه المنتجات لديها مخزون أقل من الحد الأدنى المطلوب')
                    ->options(function (Get $get) {
                        // Get products with low stock (quantity < min_stock)
                        $query = Product::query()
                            ->whereIn('type', [
                                ProductType::RawMaterial,
                                ProductType::Consumable,
                            ])
                            ->with(['category', 'inventoryItem'])
                            ->whereHas('inventoryItem', function ($inventoryQuery) {
                                $inventoryQuery->whereRaw('quantity < (SELECT avg_purchase_quantity FROM products WHERE products.id = inventory_items.product_id)');
                            });

                        // Filter by category if selected
                        if ($categoryId = $get('category_filter')) {
                            $query->where('category_id', $categoryId);
                        }

                        $this->products = $query->get();

                        return $this->products->mapWithKeys(function ($product) {
                            $price = $product->cost ?? $product->price;
                            $categoryName = $product->category ? $product->category->name : 'بدون فئة';
                            $currentStock = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                            $avgPurchaseQty = $product->avg_purchase_quantity ?? 1;
                            $needed = $avgPurchaseQty - $currentStock;

                            return [
                                $product->id => $product->name.' - '.$price.' '.currency_symbol().' ('.$categoryName.') - مقترح: '.$needed,
                            ];
                        });
                    })
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->reactive()
                    ->descriptions(function (Get $get) {
                        return $this->products->mapWithKeys(function ($product) {
                            $cost = $product->cost ?? 0;
                            $price = $product->price ?? 0;
                            $currentStock = $product->inventoryItem ? $product->inventoryItem->quantity : 0;
                            $minStock = $product->min_stock ?? 0;
                            $avgPurchaseQty = $product->avg_purchase_quantity ?? 1;

                            $description = "المخزون الحالي: {$currentStock} | الحد الأدنى: {$minStock} | متوسط كمية الشراء: {$avgPurchaseQty}";
                            $description .= " | سعر التكلفة: {$cost} ".currency_symbol()." | سعر البيع: {$price} ".currency_symbol();

                            if ($product->unit) {
                                $description .= " | الوحدة: {$product->unit}";
                            }

                            return [$product->id => $description];
                        });
                    }),
            ])
            ->action(function (array $data, Set $set, Get $get) {
                $selectedProducts = $data['selected_products'] ?? [];

                if (empty($selectedProducts)) {
                    Notification::make()
                        ->title('لا يوجد منتجات محددة')
                        ->body('يرجى اختيار المنتجات التي تريد إضافتها')
                        ->warning()
                        ->send();

                    return;
                }

                $products = Product::with('inventoryItem')
                    ->whereIn('id', $selectedProducts)
                    ->get();

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
                        $price = $product->cost ?? $product->price;
                        $avgPurchaseQty = $product->avg_purchase_quantity ?? 1;
                        $quantity = $avgPurchaseQty; // Use average purchase quantity
                        $total = $quantity * $price;

                        $currentItems[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => $quantity,
                            'price' => $price,
                            'total' => $total,
                            ...($this->additional ? ($this->additional)($product) : []),
                        ];
                        $addedCount++;
                    }
                }

                $set('items', $currentItems);

                // Recalculate invoice total
                $invoiceTotal = 0;
                foreach ($currentItems as $item) {
                    $invoiceTotal += $item['total'] ?? 0;
                }
                $set('total', $invoiceTotal);

                // Show notification
                $message = "تم إضافة {$addedCount} منتج بنجاح";
                if ($skippedCount > 0) {
                    $message .= " وتم تجاهل {$skippedCount} منتج موجود مسبقاً";
                }

                Notification::make()
                    ->title('تم استيراد المنتجات حسب معدل الشراء')
                    ->body($message)
                    ->success()
                    ->send();
            })
            ->modalHeading('استيراد المنتجات حسب معدل الشراء')
            ->modalSubheading('هذه المنتجات تحتاج إلى إعادة تخزين لأن مخزونها أقل من الحد الأدنى المطلوب. الكمية المقترحة هي متوسط كمية الشراء المعتادة.')
            ->modalWidth('2xl')
            ->slideOver();
    }
}
