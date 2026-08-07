<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The internal ₹ cost figure behind the buyer-facing FOB `price` —
     * distinct fields on purpose. OC carries the FOB price to the buyer and
     * this cost price to the PO, which prices the supplier in ₹, never FOB.
     */
    public function up(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
