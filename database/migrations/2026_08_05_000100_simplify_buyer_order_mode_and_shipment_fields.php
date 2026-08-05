<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two quick-fix requests on the Buyer master:
 *
 * 1. `order_mode` ("OC Required" / "Direct Order") is dropped. Nothing outside
 *    the Buyer module read it yet — there is no OC screen built — so removing
 *    it is contained to this table.
 *
 * 2. The single `shipment_method_id` default and the `buyer_shipment_method`
 *    accepted-set pivot are replaced by one free-typed `shipment_method`
 *    column, the same treatment Product's unit fields already get. "Air +
 *    Sea" is still a normal answer — it is just typed instead of picked from
 *    two dropdowns.
 *
 * The `shipment_methods` lookup table itself is left in place; nothing else
 * references it, so there is nothing to migrate off of it besides these two
 * buyer columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('shipment_method', 120)->nullable()->after('incoterm_id');
        });

        // Carry any existing default across as a starting value, so an
        // existing buyer does not silently lose what was already picked.
        DB::table('buyers')
            ->whereNotNull('shipment_method_id')
            ->update([
                'shipment_method' => DB::table('shipment_methods')
                    ->whereColumn('shipment_methods.id', 'buyers.shipment_method_id')
                    ->select('name'),
            ]);

        Schema::dropIfExists('buyer_shipment_method');

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex(['order_mode']);
            $table->dropColumn('order_mode');

            $table->dropForeign(['shipment_method_id']);
            $table->dropColumn('shipment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->enum('order_mode', ['oc', 'direct'])->default('oc')->after('name_on_export_invoice');
            $table->index('order_mode');

            $table->foreignId('shipment_method_id')->nullable()->after('incoterm_id')
                ->constrained('shipment_methods')->nullOnDelete();
        });

        Schema::create('buyer_shipment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_method_id')->constrained('shipment_methods')->restrictOnDelete();

            $table->unique(['buyer_id', 'shipment_method_id']);
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn('shipment_method');
        });
    }
};
