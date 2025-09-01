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
        Schema::create('inventory_item_movement_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('date');
            $table->decimal('start_quantity', 10, 2)->default(0);
            $table->decimal('incoming_quantity', 10, 2)->default(0);
            $table->decimal('return_sales_quantity', 10, 2)->default(0);
            $table->decimal('sales_quantity', 10, 2)->default(0);
            $table->decimal('return_waste_quantity', 10, 2)->default(0);
            $table->decimal('end_quantity', 10, 2)->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            // Unique constraint for product_id and date
            $table->unique(['product_id', 'date']);

            // Indexes for better performance
            $table->index('date');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_movement_daily');
    }
};
