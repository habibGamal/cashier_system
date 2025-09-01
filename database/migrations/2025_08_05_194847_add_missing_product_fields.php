<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_ref')) {
                $table->string('product_ref')->nullable()->after('name');
            }
        });

        // Update existing products to have a product_ref if they don't have one
        $products = DB::table('products')->whereNull('product_ref')->orWhere('product_ref', '')->get();
        foreach ($products as $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['product_ref' => 'PROD_' . str_pad($product->id, 6, '0', STR_PAD_LEFT)]);
        }

        // Now make it required and unique
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_ref')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_ref')) {
                $table->dropUnique(['product_ref']);
                $table->dropColumn('product_ref');
            }
        });
    }
};
