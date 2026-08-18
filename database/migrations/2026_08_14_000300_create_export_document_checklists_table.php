<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (Export Document, checklist type[, format variant]) — the
 * live version of the sheet's status/date columns. Stub rows are created for
 * every active DocumentChecklistType when an Export Document is raised (see
 * ExportDocumentService::ensureChecklist()), then filled in as documents come
 * in or get generated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_document_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_checklist_type_id')->constrained()->restrictOnDelete();

            // Which entry of the type's variant_labels this row is for; null
            // when the type has no variants.
            $table->string('variant_code', 60)->nullable();

            $table->string('status', 20)->default('pending');

            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('generated_at')->nullable();

            // LEO no., container/seal no., etc. — whatever reference text the
            // sheet asked to be "recorded" alongside this row.
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['export_document_id', 'document_checklist_type_id', 'variant_code'],
                'export_doc_checklist_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_document_checklists');
    }
};
