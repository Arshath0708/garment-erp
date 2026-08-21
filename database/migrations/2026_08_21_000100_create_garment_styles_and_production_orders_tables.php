<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garment_styles', function (Blueprint $table) {
            $table->id();
            $table->string('style_number')->unique();
            $table->string('name');
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('season')->nullable();
            $table->string('color')->nullable();
            $table->string('design')->nullable();
            $table->string('fabric')->nullable();
            $table->string('sizes')->nullable();
            $table->integer('target_qty')->default(0);
            $table->string('logo_path')->nullable();
            $table->string('image_path')->nullable();
            $table->text('tech_specs')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('order_confirmation_id')->nullable()->constrained('order_confirmations')->nullOnDelete();
            $table->foreignId('garment_style_id')->nullable()->constrained('garment_styles')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->nullOnDelete();
            $table->integer('total_qty')->default(0);
            $table->date('target_date')->nullable();
            $table->string('current_stage')->default('Cutting');
            $table->string('status')->default('In Progress');
            $table->integer('cutting_qty')->default(0);
            $table->integer('stitching_qty')->default(0);
            $table->integer('finishing_qty')->default(0);
            $table->integer('qc_passed_qty')->default(0);
            $table->integer('qc_rejected_qty')->default(0);
            $table->integer('packing_qty')->default(0);
            $table->integer('dispatch_qty')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('garment_styles');
    }
};
