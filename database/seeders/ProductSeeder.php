<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Printer;
use App\Models\InventoryItem;
use App\Enums\ProductType;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->toArray();
        $printerIds = Printer::pluck('id')->toArray();

        $products = [
            // المشروبات
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'شاي أحمر',
                'price' => 15.00,
                'cost' => 5.00,
                'min_stock' => 20.00,
                'type' => ProductType::Manufactured,
                'unit' => 'كوب',
                'legacy' => false,
                'initial_stock' => 100, // Initial stock quantity
            ],
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'قهوة تركي',
                'price' => 20.00,
                'cost' => 8.00,
                'min_stock' => 20.00,
                'type' => ProductType::Manufactured,
                'unit' => 'فنجان',
                'legacy' => false,
                'initial_stock' => 100,
            ],
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'عصير برتقال طازج',
                'price' => 25.00,
                'cost' => 12.00,
                'min_stock' => 10.00,
                'type' => ProductType::Manufactured,
                'unit' => 'كوب',
                'legacy' => false,
                'initial_stock' => 50,
            ],

            // الطعام الرئيسي
            [
                'category_id' => $categoryIds[1] ?? 1,
                'name' => 'فراخ مشوية',
                'price' => 120.00,
                'cost' => 80.00,
                'min_stock' => 5.00,
                'type' => ProductType::Manufactured,
                'unit' => 'وجبة',
                'legacy' => false,
                'initial_stock' => 30,
            ],
            [
                'category_id' => $categoryIds[1] ?? 1,
                'name' => 'كباب لحمة',
                'price' => 150.00,
                'cost' => 100.00,
                'min_stock' => 5.00,
                'type' => ProductType::Manufactured,
                'unit' => 'وجبة',
                'legacy' => false,
                'initial_stock' => 25,
            ],
            [
                'category_id' => $categoryIds[1] ?? 1,
                'name' => 'سمك مشوي',
                'price' => 180.00,
                'cost' => 120.00,
                'min_stock' => 3.00,
                'type' => ProductType::Manufactured,
                'unit' => 'وجبة',
                'legacy' => false,
                'initial_stock' => 20,
            ],

            // المقبلات
            [
                'category_id' => $categoryIds[2] ?? 1,
                'name' => 'حمص شامي',
                'price' => 30.00,
                'cost' => 15.00,
                'min_stock' => 10.00,
                'type' => ProductType::Manufactured,
                'unit' => 'طبق',
                'legacy' => false,
                'initial_stock' => 50,
            ],
            [
                'category_id' => $categoryIds[2] ?? 1,
                'name' => 'بابا غنوج',
                'price' => 35.00,
                'cost' => 18.00,
                'min_stock' => 10.00,
                'type' => ProductType::Manufactured,
                'unit' => 'طبق',
                'legacy' => false,
                'initial_stock' => 50,
            ],

            // الحلويات
            [
                'category_id' => $categoryIds[3] ?? 1,
                'name' => 'مهلبية',
                'price' => 40.00,
                'cost' => 20.00,
                'min_stock' => 10.00,
                'type' => ProductType::Manufactured,
                'unit' => 'قطعة',
                'legacy' => false,
                'initial_stock' => 40,
            ],
            [
                'category_id' => $categoryIds[3] ?? 1,
                'name' => 'كنافة بالجبنة',
                'price' => 60.00,
                'cost' => 35.00,
                'min_stock' => 5.00,
                'type' => ProductType::Manufactured,
                'unit' => 'قطعة',
                'legacy' => false,
                'initial_stock' => 30,
            ],

            // السلطات
            [
                'category_id' => $categoryIds[4] ?? 1,
                'name' => 'سلطة خضراء',
                'price' => 25.00,
                'cost' => 12.00,
                'min_stock' => 15.00,
                'type' => ProductType::Manufactured,
                'unit' => 'طبق',
                'legacy' => false,
                'initial_stock' => 60,
            ],
            [
                'category_id' => $categoryIds[4] ?? 1,
                'name' => 'تبولة',
                'price' => 30.00,
                'cost' => 15.00,
                'min_stock' => 15.00,
                'type' => ProductType::Manufactured,
                'unit' => 'طبق',
                'legacy' => false,
                'initial_stock' => 60,
            ],

            // Raw Materials
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'أرز مصري',
                'price' => 35.00,
                'cost' => 25.00,
                'min_stock' => 50.00,
                'type' => ProductType::RawMaterial,
                'unit' => 'كيلو',
                'legacy' => false,
                'initial_stock' => 500,
            ],
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'دقيق أبيض',
                'price' => 15.00,
                'cost' => 12.00,
                'min_stock' => 30.00,
                'type' => ProductType::RawMaterial,
                'unit' => 'كيلو',
                'legacy' => false,
                'initial_stock' => 200,
            ],
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'زيت طبخ',
                'price' => 45.00,
                'cost' => 35.00,
                'min_stock' => 20.00,
                'type' => ProductType::RawMaterial,
                'unit' => 'لتر',
                'legacy' => false,
                'initial_stock' => 100,
            ],

            // Consumables
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'أطباق ورقية',
                'price' => 2.00,
                'cost' => 1.50,
                'min_stock' => 100.00,
                'type' => ProductType::Consumable,
                'unit' => 'قطعة',
                'legacy' => false,
                'initial_stock' => 1000,
            ],
            [
                'category_id' => $categoryIds[0] ?? 1,
                'name' => 'أكواب بلاستيك',
                'price' => 1.50,
                'cost' => 1.00,
                'min_stock' => 100.00,
                'type' => ProductType::Consumable,
                'unit' => 'قطعة',
                'legacy' => false,
                'initial_stock' => 1000,
            ],
        ];

        foreach ($products as $index => $productData) {
            // Extract initial_stock before creating the product
            $initialStock = $productData['initial_stock'] ?? 0;
            unset($productData['initial_stock']);

            // Add product_ref to the data
            $productData['product_ref'] = 'P' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);

            // Create the product (observer will create inventory item with quantity 0)
            $product = Product::create($productData);

            // Update the inventory item with the initial stock quantity
            if ($initialStock > 0) {
                $inventoryItem = InventoryItem::where('product_id', $product->id)->first();
                if ($inventoryItem) {
                    $inventoryItem->update(['quantity' => $initialStock]);
                }
            }
        }
    }
}
