<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name' => 'وسط البلد', 'delivery_cost' => 15.00],
            ['name' => 'النزهة', 'delivery_cost' => 20.00],
            ['name' => 'مدينة نصر', 'delivery_cost' => 25.00],
            ['name' => 'الزمالك', 'delivery_cost' => 20.00],
            ['name' => 'المعادي', 'delivery_cost' => 30.00],
            ['name' => 'مصر الجديدة', 'delivery_cost' => 25.00],
            ['name' => 'الدقي', 'delivery_cost' => 25.00],
            ['name' => 'المهندسين', 'delivery_cost' => 25.00],
            ['name' => 'جسر السويس', 'delivery_cost' => 35.00],
            ['name' => 'المقطم', 'delivery_cost' => 40.00],
            ['name' => '6 أكتوبر', 'delivery_cost' => 50.00],
            ['name' => 'الشيخ زايد', 'delivery_cost' => 55.00],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
