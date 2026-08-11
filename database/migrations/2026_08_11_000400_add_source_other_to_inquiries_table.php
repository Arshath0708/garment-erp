<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #8 — Inquiry Source: "Add 'Other' as a selectable option
 * under Inquiry Source" — Inquiry::SOURCES already has an `other` value; what
 * was missing was somewhere to say what "other" means. Free text, captured
 * only when `source = other` — same conditional-field treatment as
 * SupplierRequest's gst_number/msme_registration_no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('source_other', 150)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('source_other');
        });
    }
};
