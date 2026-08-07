<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('oc_num')->unique();
            $table->string('financial_year', 10);

            // 'direct' skips the OC document entirely — items are entered at
            // PO stage and the contract number is only an anchor. See
            // OrderConfirmation::MODES.
            $table->enum('mode', ['oc', 'direct'])->default('oc');

            $table->date('oc_date');
            $table->string('buyer_ref')->nullable();

            // Set when this OC was raised from a confirmed Inquiry rather
            // than created directly.
            $table->foreignId('source_inquiry_id')->nullable()->constrained('inquiries')->nullOnDelete();

            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_format_id')->constrained('document_formats')->restrictOnDelete();

            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('agent_commission_type', ['percent', 'flat'])->nullable();
            $table->decimal('agent_commission_value', 12, 4)->nullable();

            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('incoterm')->nullable();

            $table->string('ship_method')->nullable();
            // Free text on purpose — the prototype's own field takes "March
            // 2026", not a calendar date.
            $table->string('shipment_date')->nullable();
            $table->string('pol')->nullable();
            $table->string('pod')->nullable();
            $table->string('payment_terms')->nullable();

            $table->text('delivery_details')->nullable();
            $table->text('packing_details')->nullable();
            $table->text('remarks')->nullable();

            $table->enum('status', ['draft', 'sent', 'confirmed'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('financial_year');
        });

        Schema::create('order_confirmation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_confirmation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('design_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit')->nullable();
            $table->foreignId('fob_value_id')->nullable()->constrained('fob_values')->nullOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            // Internal — never printed on the OC itself, only read forward by PO.
            $table->decimal('cost_price', 12, 2)->nullable();

            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 14, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->json('custom_values')->nullable();
        });

        Schema::create('order_confirmation_item_colours', function (Blueprint $table) {
            $table->id();
            // Explicit short constraint name — the auto-generated one (table
            // + column + "_foreign") exceeds MySQL's 64-char identifier limit
            // on these long, descriptive table names.
            $table->foreignId('order_confirmation_item_id')
                ->constrained(indexName: 'oc_item_colours_item_fk')->cascadeOnDelete();
            $table->string('colour')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('order_confirmation_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_confirmation_item_colour_id')
                ->constrained(indexName: 'oc_item_sizes_colour_fk')->cascadeOnDelete();
            $table->string('size', 20);
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmation_item_sizes');
        Schema::dropIfExists('order_confirmation_item_colours');
        Schema::dropIfExists('order_confirmation_items');
        Schema::dropIfExists('order_confirmations');
    }
};
