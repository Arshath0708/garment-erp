<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #5 — Jobwork: "Jobber menu must have an option to add
 * client name and details."
 *
 * Jobwork-only, same treatment as `we_supply_material` /
 * `requires_sample_approval` / `default_delivery_mode` in the 000200 create
 * migration — not on the original sheet, nullable, and only shown on the
 * Jobber screen when the party does jobwork.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('client_name', 200)->nullable()->after('default_delivery_mode');
            $table->text('client_details')->nullable()->after('client_name');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'client_details']);
        });
    }
};
