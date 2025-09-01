<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\ExpenceType;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $expenseTypes = ExpenceType::all();

        if ($expenseTypes->isEmpty()) {
            return;
        }

        // Create sample expenses for the last 30 days
        for ($i = 1; $i <= 20; $i++) {
            $expenseType = $expenseTypes->random();

            Expense::create([
                'expence_type_id' => $expenseType->id,
                'amount' => rand(50, 1000),
                'notes' => 'مصروف ' . $expenseType->name . ' - ' . 'شهر ' . now()->format('m/Y'),
                'shift_id' => Shift::factory()->create(['closed' => true])->id, // Create a new shift for each expense
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
