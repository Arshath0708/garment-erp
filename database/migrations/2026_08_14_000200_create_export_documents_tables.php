<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Export Document — one outward consignment to one buyer, raised from a
 * confirmed Order Confirmation the same way a Purchase Order is (see
 * OrderConfirmationService::raisePurchaseOrders()). Sidebar/permission name
 * per config/permissions.php's Export group: "the booking, container and BL
 * details are what the invoice, packing list and COO print from, so they are
 * captured on the document set itself" — this table is that document set's
 * header, ExportDocumentChecklist (next migration) is what it carries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_num')->unique();
            $table->string('financial_year', 10);

            $table->foreignId('order_confirmation_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();

            // Nullable: the source OC stores these as free-typed strings
            // (see order_confirmations.incoterm/ship_method/pod), so raising
            // an Export Document can only best-effort match them to these
            // lookups — the create form lets the user confirm/fill them in.
            $table->foreignId('incoterm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('port_of_loading_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('port_of_discharge_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('shipment_method_id')->nullable()->constrained()->nullOnDelete();

            $table->date('shipment_date')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'closed'])->default('draft');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('financial_year');
        });

        // Goods being shipped — copied from the OC's items at raise time,
        // same shape as purchase_order_items.
        Schema::create('export_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_confirmation_item_id')->nullable()->constrained('order_confirmation_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('design_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit')->nullable();
            $table->decimal('price', 12, 2)->nullable();

            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 14, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->json('custom_values')->nullable();
        });

        Schema::create('export_document_item_colours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_item_id')
                ->constrained(indexName: 'exp_item_colours_item_fk')->cascadeOnDelete();
            $table->string('colour')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('export_document_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_document_item_colour_id')
                ->constrained(indexName: 'exp_item_sizes_colour_fk')->cascadeOnDelete();
            $table->string('size', 20);
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_document_item_sizes');
        Schema::dropIfExists('export_document_item_colours');
        Schema::dropIfExists('export_document_items');
        Schema::dropIfExists('export_documents');
    }
};
