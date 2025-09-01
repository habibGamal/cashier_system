<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'شركة الطازج للخضروات',
                'phone' => '01234567890',
                'address' => 'سوق الجملة - القاهرة'
            ],
            [
                'name' => 'مؤسسة الجودة للحوم',
                'phone' => '01123456789',
                'address' => 'شارع الجمهورية - الجيزة'
            ],
            [
                'name' => 'شركة النيل للألبان',
                'phone' => '01012345678',
                'address' => 'طريق القاهرة الإسكندرية الصحراوي'
            ],
            [
                'name' => 'مورد المواد الغذائية الجافة',
                'phone' => '01567890123',
                'address' => 'العتبة - وسط البلد'
            ],
            [
                'name' => 'شركة البحر الأحمر للأسماك',
                'phone' => '01456789012',
                'address' => 'سوق السمك - الجيزة'
            ],
            [
                'name' => 'مؤسسة الذهبي للتوابل',
                'phone' => '01345678901',
                'address' => 'خان الخليلي - القاهرة'
            ],
            [
                'name' => 'شركة العربي للمشروبات',
                'phone' => '01890123456',
                'address' => 'المنطقة الصناعية - 6 أكتوبر'
            ],
            [
                'name' => 'مورد الخبز والمعجنات',
                'phone' => '01789012345',
                'address' => 'شارع فيصل - الجيزة'
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
