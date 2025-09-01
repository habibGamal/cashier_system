<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->string('type');
            $table->decimal('sub_total', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('service', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('temp_discount_percent', 5, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('profit', 10, 2);
            $table->string('payment_status');
            $table->string('dine_table_number')->nullable();
            $table->text('kitchen_notes')->nullable();
            $table->text('order_notes')->nullable();
            $table->integer('order_number');
            $table->timestamps();

            $table->unique(['shift_id', 'order_number']); // Order number unique per shift
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
