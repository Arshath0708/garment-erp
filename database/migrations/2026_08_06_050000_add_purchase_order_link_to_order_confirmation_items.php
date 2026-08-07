<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split into its own migration, after purchase_orders exists — an OC
     * item points forward at the PO it was raised onto, and the two create
     * tables can't reference each other's not-yet-existing table in one pass.
     *
     * Whether an item can be raised again is read straight off
     * purchase_order_id being null, rather than a separate join table.
     */
    public function up(): void
    {
        Schema::table('order_confirmation_items', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('order_confirmation_id')
                ->constrained('purchase_orders')->nullOnDelete();
            $table->timestamp('raised_at')->nullable()->after('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_confirmation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropColumn('raised_at');
        });
    }
};
