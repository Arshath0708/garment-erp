<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('reorder_level', 14, 3)->default(0)->after('qty_on_hand');
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->string('invoice_layout', 30)->default('standard')->after('name_on_export_invoice');
        });

        Schema::table('garment_styles', function (Blueprint $table) {
            $table->unsignedInteger('bom_version')->default(1)->after('status');
            $table->timestamp('bom_approved_at')->nullable()->after('bom_version');
            $table->foreignId('bom_approved_by')->nullable()->after('bom_approved_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('garment_style_bom_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_style_id')->constrained('garment_styles')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('materials');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['garment_style_id', 'version']);
        });

        Schema::table('inward_entries', function (Blueprint $table) {
            $table->timestamp('stores_received_at')->nullable()->after('qc_inspected_by');
            $table->foreignId('stores_received_by')->nullable()->after('stores_received_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('job_work_vouchers', function (Blueprint $table) {
            $table->decimal('rate_per_pc', 12, 4)->default(0)->after('damaged_qty');
            $table->decimal('charge_amount', 14, 2)->default(0)->after('rate_per_pc');
        });

        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_num')->unique();
            $table->string('financial_year', 10);
            $table->date('note_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->unsignedInteger('qty')->default(0);
            $table->string('reason', 80)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedInteger('target_pcs_per_day')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_line_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->cascadeOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->date('output_date');
            $table->unsignedInteger('pcs')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_line_id', 'output_date']);
        });

        DB::table('production_lines')->insert([
            ['name' => 'Line 1', 'target_pcs_per_day' => 500, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Line 2', 'target_pcs_per_day' => 500, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Line 3', 'target_pcs_per_day' => 400, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('production_line_outputs');
        Schema::dropIfExists('production_lines');
        Schema::dropIfExists('debit_notes');

        Schema::table('job_work_vouchers', function (Blueprint $table) {
            $table->dropColumn(['rate_per_pc', 'charge_amount']);
        });

        Schema::table('inward_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stores_received_by');
            $table->dropColumn('stores_received_at');
        });

        Schema::dropIfExists('garment_style_bom_snapshots');

        Schema::table('garment_styles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bom_approved_by');
            $table->dropColumn(['bom_version', 'bom_approved_at']);
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn('invoice_layout');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });
    }
};
