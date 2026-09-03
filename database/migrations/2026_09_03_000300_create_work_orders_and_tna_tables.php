<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_num')->unique();
            $table->string('financial_year', 10);
            $table->date('wo_date');
            $table->foreignId('garment_style_id')->constrained('garment_styles')->restrictOnDelete();
            $table->foreignId('order_confirmation_id')->nullable()->constrained('order_confirmations')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total_qty');
            $table->date('target_date');
            $table->string('status', 20)->default('draft');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'target_date']);
            $table->index(['garment_style_id', 'order_confirmation_id', 'status']);
        });

        Schema::create('time_and_action_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->string('step_key', 40);
            $table->string('label', 80);
            $table->unsignedTinyInteger('sort_order');
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->timestamps();

            $table->unique(['work_order_id', 'step_key']);
            $table->index(['planned_date', 'actual_date']);
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('work_order_id')->nullable()->after('order_confirmation_id')
                ->constrained('work_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_order_id');
        });
        Schema::dropIfExists('time_and_action_steps');
        Schema::dropIfExists('work_orders');
    }
};
