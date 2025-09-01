<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Printer;

class PrinterSeeder extends Seeder
{
    public function run(): void
    {
        $printers = [
            ['name' => 'طابعة المطبخ الرئيسي', 'ip_address' => '192.168.1.100'],
            ['name' => 'طابعة البار', 'ip_address' => '192.168.1.101'],
            ['name' => 'طابعة المشويات', 'ip_address' => '192.168.1.102'],
            ['name' => 'طابعة الحلويات', 'ip_address' => '192.168.1.103'],
            ['name' => 'طابعة الكاشير', 'ip_address' => '192.168.1.104'],
        ];

        foreach ($printers as $printer) {
            Printer::create($printer);
        }
    }
}
