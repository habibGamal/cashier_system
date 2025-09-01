<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenceType;

class ExpenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $expenseTypes = [
            ['name' => 'مرافق (كهرباء - غاز - مياه)'],
            ['name' => 'إيجار المحل'],
            ['name' => 'رواتب الموظفين'],
            ['name' => 'مواد تنظيف'],
            ['name' => 'صيانة المعدات'],
            ['name' => 'وقود وتنقلات'],
            ['name' => 'دعاية وإعلان'],
            ['name' => 'مصاريف إدارية'],
            ['name' => 'ضرائب ورسوم'],
            ['name' => 'تأمينات'],
            ['name' => 'اتصالات وإنترنت'],
            ['name' => 'مصاريف أخرى'],
        ];

        foreach ($expenseTypes as $type) {
            ExpenceType::create($type);
        }
    }
}
