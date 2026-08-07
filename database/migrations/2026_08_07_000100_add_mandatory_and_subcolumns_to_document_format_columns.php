<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two more things a column can carry, both from the reference prototype's
 * column builder: whether it's required when creating downstream rows
 * ("Mandatory"), and — for the Size column specifically — the fixed list of
 * size tags (S, M, L, XL…) that turn the free-form size/qty entry on
 * Inquiry/OC/PO item rows into a fixed qty-per-size grid.
 *
 * `sub_columns` is stored generically (any column could carry it) rather
 * than as a Size-only field, matching how the prototype itself models it —
 * but every renderer only ever reads it off the `size` key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_format_columns', function (Blueprint $table) {
            $table->boolean('is_mandatory')->default(false)->after('is_enabled');
            $table->json('sub_columns')->nullable()->after('print_only');
        });
    }

    public function down(): void
    {
        Schema::table('document_format_columns', function (Blueprint $table) {
            $table->dropColumn(['is_mandatory', 'sub_columns']);
        });
    }
};
