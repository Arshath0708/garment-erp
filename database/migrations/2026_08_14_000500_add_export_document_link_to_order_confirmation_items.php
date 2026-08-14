<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non-breaking addition — same treatment as this table's existing
 * purchase_order_id/raised_at pair. An OC item can be raised onto a PO
 * (production, supplier side) and, independently, onto an Export Document
 * (shipment, buyer side); this is the second of those two links. Deliberately
 * absent from OrderConfirmationItem::$fillable so the ordinary item resync in
 * OrderConfirmationService::syncItems() can never touch it — see that
 * model's docblock for the existing pair this mirrors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_confirmation_items', function (Blueprint $table) {
            $table->foreignId('export_document_id')->nullable()->after('raised_at')
                ->constrained()->nullOnDelete();
            $table->timestamp('shipped_at')->nullable()->after('export_document_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_confirmation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('export_document_id');
            $table->dropColumn('shipped_at');
        });
    }
};
