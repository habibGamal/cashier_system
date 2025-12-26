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

class ProductComponentsImporterAction extends Action
{
    protected $products;

    public static function getDefaultName(): ?string
    {
        return 'productComponentsImporter';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = collect();
        $this->label('استيراد المكونات')
            ->icon('heroicon-m-plus-circle')
            ->color('success')
            ->form([
                Select::make('selected_collection')
                    ->label('المكونات المختارة')
                    ->options(function () {
                        return Product::whereIn('type', [ProductType::RawMaterial->value, ProductType::Consumable->value])
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->multiple(),

                Select::make('category_filter')
                    ->label('فلترة حسب الفئة')
                    ->placeholder('جميع الفئات')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('selected_products', [])),

                CheckboxList::make('selected_products')
                    ->label('اختر المكونات للإضافة إلى الوصفة')
                    ->options(function (Get $get) {
                        $query = Product::query()
                            ->with('category')
                            ->whereIn('type', [ProductType::RawMaterial->value, ProductType::Consumable->value]);

                        // Filter by category if selected
                        if ($categoryId = $get('category_filter')) {
                            $query->where('category_id', $categoryId);
                        }

                        // Filter by search term if provided
                        if ($search = $get('search_filter')) {
                            $query->where('name', 'like', '%'.$search.'%');
                        }

                        $this->products = $query->get();

                        return $this->products->mapWithKeys(function ($product) {
                            $price = $product->cost ?? $product->price;
                            $categoryName = $product->category ? $product->category->name : 'بدون فئة';
                            $typeLabel = match ($product->type->value) {
                                'raw_material' => 'مادة خام',
                                'consumable' => 'استهلاكي',
                                default => $product->type->value
                            };

                            return [
                                $product->id => $product->name.' - '.$price.' '.currency_symbol().' ('.$categoryName.') - '.$typeLabel,
                            ];
                        });
                    })
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // When selected_products is updated, also update selected_collection to match
                        $set('selected_collection', collect([...$get('selected_collection'), ...$state])->unique()->values());
                    })
                    ->descriptions(function (Get $get) {
                        return $this->products->mapWithKeys(function ($product) {
                            $cost = $product->cost ?? 0;
                            $price = $product->price ?? 0;
                            $description = "سعر التكلفة: {$cost} ".currency_symbol()." | سعر البيع: {$price} ".currency_symbol();
                            if ($product->unit) {
                                $description .= " | الوحدة: {$product->unit}";
                            }

                            return [$product->id => $description];
                        });
                    }),
            ])
            ->action(function (array $data, Set $set, Get $get) {
                $selectedProducts = $data['selected_collection'] ?? [];
                $products = Product::whereIn('id', $selectedProducts)->with('category')->get();
                $currentComponents = $get('productComponents') ?? [];

                // Get existing component IDs to avoid duplicates
                $existingComponentIds = collect($currentComponents)->pluck('component_id')->filter()->toArray();

                $addedCount = 0;
                $skippedCount = 0;

                foreach ($selectedProducts as $productId) {
                    // Skip if component already exists in the list
                    if (in_array($productId, $existingComponentIds)) {
                        $skippedCount++;

                        continue;
                    }

                    $product = $products->where('id', $productId)->first();
                    if ($product) {
                        $currentComponents[] = [
                            'component_id' => $product->id,
                            'component_name' => $product->name,
                            'quantity' => 1,
                            'unit' => $product->unit,
                            'cost' => $product->cost ?? 0,
                            'category_name' => $product->category->name,
                        ];
                        $addedCount++;
                    }
                }

                $set('productComponents', $currentComponents);

                // Show notification
                $message = "تم إضافة {$addedCount} مكون بنجاح";
                if ($skippedCount > 0) {
                    $message .= " وتم تجاهل {$skippedCount} مكون موجود مسبقاً";
                }

                Notification::make()
                    ->title('تم استيراد المكونات')
                    ->body($message)
                    ->success()
                    ->send();
            })
            ->modalHeading('استيراد مكونات الوصفة')
            ->modalSubheading('اختر المكونات الخام والاستهلاكية التي تريد إضافتها إلى وصفة المنتج المُصنع. يمكنك استخدام الفلاتر لتسهيل البحث.')
            ->modalWidth('2xl')
            ->slideOver();
    }
}
