<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('item_kind', 20)->default('other')->after('status');
            $table->decimal('qty_on_hand', 14, 3)->default(0)->after('item_kind');
        });

        Schema::create('garment_style_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_style_id')->constrained('garment_styles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty_per_pc', 12, 4)->default(0);
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['garment_style_id', 'product_id']);
        });

        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('required_qty', 14, 3)->default(0);
            $table->decimal('use_stock_qty', 14, 3)->default(0);
            $table->decimal('buy_qty', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['production_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
        Schema::dropIfExists('garment_style_materials');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['item_kind', 'qty_on_hand']);
        });
    }
};
