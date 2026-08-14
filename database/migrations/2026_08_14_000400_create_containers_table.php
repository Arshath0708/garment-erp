<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A shipping container. Kept separate from Export Document rather than a
 * column on it: "sometimes we split the shipment into different documents
 * because we have multiple buyers sending goods in the same container" — one
 * container can carry several Export Documents, and (less often) a large
 * order's goods can span more than one container.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_no', 30);
            $table->string('seal_no', 30)->nullable();
            $table->enum('type', ['lcl', 'fcl'])->default('lcl');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('container_export_document', function (Blueprint $table) {
            $table->foreignId('container_id')->constrained()->cascadeOnDelete();
            $table->foreignId('export_document_id')->constrained()->cascadeOnDelete();
            $table->primary(['container_id', 'export_document_id'], 'container_export_doc_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_export_document');
        Schema::dropIfExists('containers');
    }
};
