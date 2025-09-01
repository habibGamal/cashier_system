<?php

namespace Database\Seeders;

use App\Models\TableType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tableTypes = [
            ['name' => 'صالة'],
            // ['name' => 'كلاسيك'],
            // ['name' => 'بدوي'],
        ];

        foreach ($tableTypes as $tableType) {
            TableType::firstOrCreate($tableType);
        }
    }
}
