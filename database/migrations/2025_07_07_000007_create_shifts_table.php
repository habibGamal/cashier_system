<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('start_cash', 10, 2);
            $table->decimal('end_cash', 10, 2)->nullable();
            $table->decimal('losses_amount', 10, 2)->nullable();
            $table->decimal('real_cash', 10, 2)->nullable();
            $table->boolean('has_deficit')->default(false);
            $table->boolean('closed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
