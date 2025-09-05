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
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_order_id')->constrained('return_orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->double('quantity');
            $table->decimal('original_price', 10, 2);
            $table->decimal('original_cost', 10, 2);
            $table->decimal('return_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('return_order_id');
            $table->index('order_item_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
