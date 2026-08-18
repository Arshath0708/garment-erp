<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the Bill of Lading (Draft) generator needs that nothing upstream
 * carries — same manual-entry treatment as the Packing List fields added in
 * 2026_08_17_000100. Notify Party is its own block (falls back to the
 * Consignee block on the print view when left blank, same idea as
 * Consignee falling back to the Buyer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->string('booking_no')->nullable()->after('carton_dimensions');
            $table->string('bl_no')->nullable()->after('booking_no');
            $table->string('voyage_no')->nullable()->after('bl_no');
            $table->string('transshipment_port')->nullable()->after('voyage_no');

            $table->string('notify_party_name')->nullable()->after('transshipment_port');
            $table->text('notify_party_address')->nullable()->after('notify_party_name');

            $table->text('goods_description')->nullable()->after('notify_party_address');
            $table->decimal('total_measurement', 12, 3)->nullable()->after('goods_description');

            $table->string('ex_rate')->nullable()->after('total_measurement');
            $table->string('freight_terms')->default('PREPAID')->after('ex_rate');
            $table->string('freight_prepaid_at')->nullable()->after('freight_terms');
            $table->string('freight_payable_at')->nullable()->after('freight_prepaid_at');
            $table->string('total_prepaid_in')->nullable()->after('freight_payable_at');

            $table->string('no_of_original_bls')->nullable()->after('total_prepaid_in');
            $table->string('bl_place_of_issue')->default('MUMBAI')->after('no_of_original_bls');
            $table->date('bl_date_of_issue')->nullable()->after('bl_place_of_issue');
        });
    }

    public function down(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->dropColumn([
                'booking_no', 'bl_no', 'voyage_no', 'transshipment_port',
                'notify_party_name', 'notify_party_address',
                'goods_description', 'total_measurement',
                'ex_rate', 'freight_terms', 'freight_prepaid_at', 'freight_payable_at', 'total_prepaid_in',
                'no_of_original_bls', 'bl_place_of_issue', 'bl_date_of_issue',
            ]);
        });
    }
};
