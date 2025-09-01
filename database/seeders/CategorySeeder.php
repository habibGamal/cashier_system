<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'المشروبات'],
            ['name' => 'الطعام الرئيسي'],
            ['name' => 'المقبلات'],
            ['name' => 'الحلويات'],
            ['name' => 'السلطات'],
            ['name' => 'المعجنات'],
            ['name' => 'المشويات'],
            ['name' => 'المقليات'],
            ['name' => 'العصائر'],
            ['name' => 'القهوة والشاي'],
            ['name' => 'المثلجات'],
            ['name' => 'الوجبات السريعة'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
