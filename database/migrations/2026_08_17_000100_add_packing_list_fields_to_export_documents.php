<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the three Packing List formats need that nothing upstream carries —
 * same treatment as the 000700 logistics migration: all manual entry, filled
 * in on the Export Document edit screen. Country of Final Destination is
 * deliberately absent — it reads off the buyer's own country
 * (Buyer::country()) rather than being typed a second time here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            // Format C's own header block, distinct from our exporter_ref.
            $table->string('buyer_ref_no')->nullable()->after('exporter_ref');
            $table->date('buyer_ref_date')->nullable()->after('buyer_ref_no');
            $table->string('other_reference')->nullable()->after('buyer_ref_date');

            // Consignee is its own block on Format C — not always the buyer
            // (a notify party or the buyer's agent, sometimes), so free text
            // rather than a second party reference.
            $table->string('consignee_name')->nullable()->after('other_reference');
            $table->text('consignee_address')->nullable()->after('consignee_name');

            $table->string('pre_carriage_by')->nullable()->after('consignee_address');
            $table->string('place_of_receipt')->nullable()->after('pre_carriage_by');
            $table->string('vessel_flight_no')->nullable()->after('place_of_receipt');
            $table->string('country_of_origin')->default('INDIA')->after('vessel_flight_no');

            // Format B/C both print a net weight and a carton/bale
            // dimension the same way they already print gross_weight (added
            // in 000700) — same manual-entry treatment.
            $table->decimal('net_weight', 12, 3)->nullable()->after('gross_weight');
            $table->string('carton_dimensions')->nullable()->after('net_weight');
        });
    }

    public function down(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_ref_no', 'buyer_ref_date', 'other_reference',
                'consignee_name', 'consignee_address',
                'pre_carriage_by', 'place_of_receipt', 'vessel_flight_no', 'country_of_origin',
                'net_weight', 'carton_dimensions',
            ]);
        });
    }
};
