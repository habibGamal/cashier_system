<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expence_types', function (Blueprint $table) {
            $table->decimal('avg_month_rate', 10, 2)->nullable()->after('name')->comment('Average monthly budget for this expense type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expence_types', function (Blueprint $table) {
            $table->dropColumn('avg_month_rate');
        });
    }
};
