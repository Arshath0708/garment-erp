<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #1 revision — "Product Category and Product, we can add
 * multiple Products, multiple categories". The single `product_id` FK added
 * in the 000100 migration is replaced with a `supplier_product` pivot, same
 * shape as the existing `supplier_category` pivot — a jobber can now be
 * linked to more than one product, same as it already can be to more than
 * one category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::create('supplier_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->unique(['supplier_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('name_on_bill')
                ->constrained()->nullOnDelete();
        });
    }
};
