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
        Schema::create('inventory_item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('operation'); // 'in' or 'out'
            $table->decimal('quantity', 8, 2);
            $table->string('reason'); // MovementReason enum values
            $table->text('notes')->nullable();
            $table->string('referenceable_type')->nullable();
            $table->unsignedBigInteger('referenceable_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'operation']);
            $table->index(['created_at']);
            $table->index(['referenceable_type', 'referenceable_id'], 'iim_referenceable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_movements');
    }
};
