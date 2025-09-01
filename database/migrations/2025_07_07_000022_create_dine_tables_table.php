<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dine_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->unique('table_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dine_tables');
    }
};
