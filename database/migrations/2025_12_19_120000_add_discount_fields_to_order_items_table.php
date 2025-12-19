<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('item_discount', 10, 2)->default(0)->after('notes');
            $table->string('item_discount_type')->nullable()->after('item_discount');
            $table->decimal('item_discount_percent', 5, 2)->nullable()->after('item_discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['item_discount', 'item_discount_type', 'item_discount_percent']);
        });
    }
};
