<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer sheet col E is a single "Contact Person" text box with no designation
 * of its own — unlike Supplier/Jobber, which carry a full contacts table with
 * `designation_id` per row (see SupplierContact). The client has since asked
 * for a designation on the buyer's contact too, so this adds the one column
 * a flat single-contact field needs rather than standing up a contacts table
 * Buyer has never had.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->foreignId('contact_designation_id')->nullable()->after('contact_person')
                ->constrained('designations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_designation_id');
        });
    }
};
