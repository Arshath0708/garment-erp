<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_work_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_num')->unique();
            $table->string('financial_year', 10);
            $table->string('type', 20);
            $table->date('voucher_date');
            $table->foreignId('jobber_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->foreignId('garment_style_id')->nullable()->constrained('garment_styles')->nullOnDelete();
            $table->string('process', 30)->nullable();
            $table->string('vehicle_no')->nullable();
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedInteger('damaged_qty')->default(0);
            $table->json('size_qty')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['jobber_id', 'type']);
            $table->index('production_order_id');
        });

        Schema::create('style_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_style_id')->unique()->constrained('garment_styles')->cascadeOnDelete();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->timestamps();
        });

        Schema::create('production_qc_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->string('stage', 30);
            $table->unsignedInteger('checked_qty')->default(0);
            $table->unsignedInteger('passed_qty')->default(0);
            $table->unsignedInteger('failed_qty')->default(0);
            $table->string('result', 20);
            $table->text('notes')->nullable();
            $table->boolean('held_work_order')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $uniqueName = null;
        foreach (Schema::getIndexes('garment_style_materials') as $index) {
            if (! empty($index['unique']) && ($index['columns'] ?? []) === ['garment_style_id', 'product_id']) {
                $uniqueName = $index['name'];
                break;
            }
        }

        Schema::table('garment_style_materials', function (Blueprint $table) use ($uniqueName) {
            if ($uniqueName) {
                $table->dropUnique($uniqueName);
            }
            $table->string('size_from', 10)->nullable()->after('unit');
            $table->string('size_to', 10)->nullable()->after('size_from');
        });

        Schema::table('export_documents', function (Blueprint $table) {
            $table->unsignedInteger('fg_posted_qty')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->dropColumn('fg_posted_qty');
        });

        Schema::table('garment_style_materials', function (Blueprint $table) {
            $table->dropColumn(['size_from', 'size_to']);
            $table->unique(['garment_style_id', 'product_id']);
        });

        Schema::dropIfExists('production_qc_checks');
        Schema::dropIfExists('style_stocks');
        Schema::dropIfExists('job_work_vouchers');
    }
};
