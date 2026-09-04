<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_costings', function (Blueprint $table) {
            $table->id();
            $table->string('costing_num')->unique();
            $table->string('financial_year', 10);
            $table->date('costing_date');
            $table->foreignId('garment_style_id')->constrained('garment_styles')->restrictOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cm_cost', 12, 4)->default(0);
            $table->decimal('other_cost', 12, 4)->default(0);
            $table->decimal('material_cost', 14, 4)->default(0);
            $table->decimal('total_cost_per_pc', 14, 4)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['garment_style_id', 'status']);
        });

        Schema::create('style_costing_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_costing_id')->constrained('style_costings')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->string('item_kind', 30)->nullable();
            $table->decimal('qty_per_pc', 12, 4)->default(0);
            $table->string('unit', 20)->nullable();
            $table->decimal('rate', 12, 4)->default(0);
            $table->decimal('amount', 14, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_costing_lines');
        Schema::dropIfExists('style_costings');
    }
};
