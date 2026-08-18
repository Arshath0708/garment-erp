<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request — Packing List Format B ("Without Supplier, for Carton")
 * and Format C ("For Export Documentation") both print carton by carton,
 * not the flat item list ExportDocumentItem already gives Format A. Neither
 * format maps a carton cleanly onto one product line — a carton's contents
 * are a free-text description the packer types (matching what a physical
 * carton actually holds), so lines are their own text/qty/unit rather than
 * a foreign key to ExportDocumentItem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_document_cartons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_id')->constrained()->cascadeOnDelete();
            $table->string('carton_no', 40);
            $table->decimal('net_weight', 10, 3)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['export_document_id', 'sort_order']);
        });

        Schema::create('export_document_carton_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_carton_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            // PCS / SETS / MTRS — Format C's own totals page breaks the
            // grand total down by exactly these three, so the unit is typed
            // per line rather than picked from Order Format's unit chips.
            $table->string('unit', 20)->default('PCS');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // MySQL's default identifier-name generator overruns the
            // 64-character limit on this table/column combination — an
            // explicit short name avoids it.
            $table->index(['export_document_carton_id', 'sort_order'], 'edcl_carton_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_document_carton_lines');
        Schema::dropIfExists('export_document_cartons');
    }
};
