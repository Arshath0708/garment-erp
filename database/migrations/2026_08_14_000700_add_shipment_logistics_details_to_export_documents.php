<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the real Delivery Challan / Export Invoice paperwork needs that
 * nothing upstream (OC, Buyer) carries — all manual entry for now, filled in
 * on the Export Document edit screen once a shipment is being packed. None
 * of this is derivable from existing data, so nothing here is auto-computed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            // Referenced on the Delivery Challan/Export Invoice paperwork —
            // this shipment's own invoice number/date, distinct from doc_num
            // (our internal GT/EXP/... tracking number).
            $table->string('invoice_no')->nullable()->after('doc_num');
            $table->date('invoice_date')->nullable()->after('invoice_no');
            $table->string('exporter_ref')->nullable()->after('invoice_date');

            // CHA / forwarder the goods are despatched to, and the vehicle
            // carrying them — printed on the Delivery Challan.
            $table->string('forwarder_name')->nullable()->after('remarks');
            $table->text('forwarder_address')->nullable()->after('forwarder_name');
            $table->string('vehicle_no')->nullable()->after('forwarder_address');
            $table->string('driver_cell')->nullable()->after('vehicle_no');

            // Marks & Nos / package count block.
            $table->string('final_destination')->nullable()->after('driver_cell');
            $table->text('marks_and_numbers')->nullable()->after('final_destination');
            $table->unsignedInteger('total_cartons')->nullable()->after('marks_and_numbers');
            $table->string('package_kind')->default('CARTONS')->after('total_cartons');

            // CIF/CFR-type incoterms and the E-way Bill variant — see the
            // plan: no agreed formula exists yet, so these stay manual entry
            // rather than an automatic calculation.
            $table->decimal('freight_amount', 14, 2)->nullable()->after('package_kind');
            $table->decimal('insurance_amount', 14, 2)->nullable()->after('freight_amount');
            $table->decimal('gross_weight', 12, 3)->nullable()->after('insurance_amount');
        });
    }

    public function down(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_no', 'invoice_date', 'exporter_ref',
                'forwarder_name', 'forwarder_address', 'vehicle_no', 'driver_cell',
                'final_destination', 'marks_and_numbers', 'total_cartons', 'package_kind',
                'freight_amount', 'insurance_amount', 'gross_weight',
            ]);
        });
    }
};
