<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 26-row checklist from the "Export docs to payment receipt from buyer"
 * sheet, turned into data instead of code — DocumentChecklistTypeSeeder fills
 * it once. A row here is one kind of document/checkpoint an Export Document
 * carries (Packing List, Bill of Lading, eBRC, ...); ExportDocumentChecklist
 * is the per-shipment status against each one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_checklist_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();

            // 'generated' — the system produces it (packing list, invoice, VGM...).
            // 'uploaded'  — a scan/copy is uploaded and a date recorded.
            // 'manual'    — a note/reference only, no file (examination, container no.).
            $table->enum('category', ['generated', 'uploaded', 'manual'])->default('uploaded');

            // Labels for the format variants a row can have, e.g. Export
            // Invoice's ["For Customs","For Buyer","For Bank","For E-way Bill"].
            // One ExportDocumentChecklist row is created per label.
            $table->json('variant_labels')->nullable();

            // Uploading/generating this row closes its Export Document — true
            // only for eBRC, per "that is when the file for that shipment is
            // closed."
            $table->boolean('closes_shipment')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_checklist_types');
    }
};
