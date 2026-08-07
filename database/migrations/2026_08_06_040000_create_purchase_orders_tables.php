<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_num')->unique();
            $table->string('financial_year', 10);

            $table->foreignId('order_confirmation_id')->constrained()->restrictOnDelete();
            // One supplier per PO — raising a PO against an OC's items groups
            // them by their own supplier and creates one PO per group.
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();

            $table->date('po_date');
            $table->date('dispatch_date')->nullable();

            $table->text('delivery_details')->nullable();
            $table->text('packing_details')->nullable();
            $table->text('remarks')->nullable();

            // 'partial' / 'received' are driven by Goods Inward, which does
            // not exist in this codebase yet — the values exist for that
            // future module to set, but nothing here can reach them.
            $table->enum('status', ['draft', 'raised', 'partial', 'received'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('financial_year');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            // Traceability back to the OC line this was raised from — not
            // enforced anywhere else, so nullable rather than required.
            $table->foreignId('order_confirmation_item_id')->nullable()->constrained('order_confirmation_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('design_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit')->nullable();
            // The ₹ figure the supplier is actually paid — carried from the
            // OC item's own cost_price, editable per PO line.
            $table->decimal('cost_price', 12, 2)->nullable();

            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 14, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->json('custom_values')->nullable();
        });

        Schema::create('purchase_order_item_colours', function (Blueprint $table) {
            $table->id();
            // Explicit short constraint name — same reasoning as
            // order_confirmation_item_colours in the 030000 migration.
            $table->foreignId('purchase_order_item_id')
                ->constrained(indexName: 'po_item_colours_item_fk')->cascadeOnDelete();
            $table->string('colour')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('purchase_order_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_item_colour_id')
                ->constrained(indexName: 'po_item_sizes_colour_fk')->cascadeOnDelete();
            $table->string('size', 20);
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('purchase_order_timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('note');
            $table->unsignedInteger('qty')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_timeline_entries');
        Schema::dropIfExists('purchase_order_item_sizes');
        Schema::dropIfExists('purchase_order_item_colours');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
