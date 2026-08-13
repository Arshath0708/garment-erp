<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jobber Master — "link the Client Name to the Buyer master instead of a free
 * text field, and allow more than one buyer to be linked." Same treatment as
 * the 000100 migration turning the single `product_id` into a
 * `supplier_product` pivot: the free-text `client_name` column is dropped and
 * replaced with a `supplier_buyer` pivot so a jobber can point at one or more
 * rows in the Buyer master. `client_details` is untouched — it is still free
 * text for whatever does not fit a buyer record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('client_name');
        });

        Schema::create('supplier_buyer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();

            $table->unique(['supplier_id', 'buyer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_buyer');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('client_name', 200)->nullable()->after('default_delivery_mode');
        });
    }
};
