<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Packing List Format B prints Total Gross Weight and Carton/Bale Dimension
 * per carton, not once for the whole shipment — export_documents.gross_weight
 * / carton_dimensions (000700/000100) are the document-level figures Format A
 * and the Delivery Challan use; this is the per-carton equivalent Format B
 * needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_document_cartons', function (Blueprint $table) {
            $table->decimal('gross_weight', 10, 3)->nullable()->after('net_weight');
            $table->string('dimensions', 60)->nullable()->after('gross_weight');
        });
    }

    public function down(): void
    {
        Schema::table('export_document_cartons', function (Blueprint $table) {
            $table->dropColumn(['gross_weight', 'dimensions']);
        });
    }
};
