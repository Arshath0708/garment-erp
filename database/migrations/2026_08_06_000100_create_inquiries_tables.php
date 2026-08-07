<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no')->unique();
            $table->string('financial_year', 10);
            $table->date('inquiry_date');
            $table->string('buyer_ref')->nullable();
            $table->string('source');

            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_format_id')->constrained('document_formats')->restrictOnDelete();

            // Snapshotted from the buyer at create time, same reasoning as
            // Buyer::agent_commission_type/value — a negotiated rate copied
            // in, not a live join that drifts if the buyer master changes.
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('agent_commission_type', ['percent', 'flat'])->nullable();
            $table->decimal('agent_commission_value', 12, 4)->nullable();

            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('exchange_rate', 12, 4)->nullable();
            $table->date('expected_shipment_date')->nullable();

            // Copied in from the format at create time, editable per inquiry —
            // same "pre-fills, editable per record" contract documented on
            // DocumentFormat::$delivery_details / $packing_details.
            $table->text('delivery_details')->nullable();
            $table->text('packing_details')->nullable();

            $table->text('remarks')->nullable();

            $table->enum('status', [
                'draft', 'price_working', 'quote_sent', 'confirmed', 'converted_to_oc', 'lost',
            ])->default('draft');
            $table->timestamp('converted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('financial_year');
        });

        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('design_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit')->nullable();
            $table->foreignId('fob_value_id')->nullable()->constrained('fob_values')->nullOnDelete();
            $table->decimal('price', 12, 2)->nullable();

            // Stored totals — summed from inquiry_item_sizes by InquiryService
            // on every save, same "compute once, store the total" discipline as
            // Markup::profit(). Never written directly from request input.
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 14, 2)->default(0);

            $table->enum('status', [
                'draft', 'price_working', 'quote_sent', 'confirmed', 'converted_to_oc', 'lost',
            ])->default('draft');
            $table->text('remarks')->nullable();

            $table->index('status');
        });

        Schema::create('inquiry_item_colours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_item_id')->constrained()->cascadeOnDelete();
            $table->string('colour')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('inquiry_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_item_colour_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20);
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('inquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->text('comment');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_follow_ups');
        Schema::dropIfExists('inquiry_item_sizes');
        Schema::dropIfExists('inquiry_item_colours');
        Schema::dropIfExists('inquiry_items');
        Schema::dropIfExists('inquiries');
    }
};
