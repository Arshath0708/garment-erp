<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #1 — Jobber Master: "Add a Product field on the Jobber
 * form, rendered as a dropdown (select from existing Products)."
 *
 * Nullable and on the shared `suppliers` table because Jobber is a
 * `party_type` filter on Supplier, not a separate table — see the 000200
 * create migration's docblock. Category linkage (col R) is unaffected; this
 * is additive, for a jobber that makes one specific product rather than a
 * whole category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('name_on_bill')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
