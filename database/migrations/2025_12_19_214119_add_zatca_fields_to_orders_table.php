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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('zatca_status')->nullable()->after('payment_status'); // PENDING, REPORTED, CLEARED, FAILED
            $table->string('zatca_uuid')->nullable()->after('zatca_status');
            $table->string('zatca_hash')->nullable()->after('zatca_uuid');
            $table->text('zatca_qr_base64')->nullable()->after('zatca_hash');
            $table->string('zatca_xml_path')->nullable()->after('zatca_qr_base64');
            $table->text('zatca_last_response')->nullable()->after('zatca_xml_path');
            $table->timestamp('zatca_submitted_at')->nullable()->after('zatca_last_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'zatca_status',
                'zatca_uuid',
                'zatca_hash',
                'zatca_qr_base64',
                'zatca_xml_path',
                'zatca_last_response',
                'zatca_submitted_at',
            ]);
        });
    }
};
