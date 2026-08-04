<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Order Format master, built out from the five-column stub.
 *
 * `document_formats` was created as a placeholder so `categories.po_format_id`
 * could be a real foreign key rather than a loose integer — name, module,
 * blade_view, status. The client's prototype shows what the screen actually
 * is: "Define the column structure for this purchase order format". A format
 * decides the shape of the item table on every Inquiry, OC and PO that uses
 * it, the units those rows may be priced in, and the delivery and packing text
 * that prints beneath them.
 *
 * Four things come out of the prototype and land here:
 *
 *   allow_multiple_colours  "When on, each item row in Inquiry / OC / PO gets
 *                           a + Add colour button. All colour rows share the
 *                           same Design, Product, Supplier, FOB and size
 *                           breakdown." A per-format shape decision, so it
 *                           belongs on the format, not on the order.
 *   units                   "Units defined here are shared across all
 *                           connected modules. The selected unit in a PO row
 *                           will auto-populate the label in the Price column
 *                           header (e.g. Price / PCS)."
 *   columns                 The item table. Standard columns can be switched
 *                           off and relabelled; custom ones can be added.
 *   delivery/packing        "Pre-fills when a PO is created — editable per PO."
 *
 * ## Why units are per-format and not a Unit Master
 *
 * The lookup migration records the client's ruling: "I don't see a need for
 * the unit master". That still holds — this is not a master with its own
 * screen, a code and a status. It is the short list of units this one format
 * offers, edited on the format itself, exactly as the prototype draws it.
 *
 * ## Audit and soft deletes
 *
 * The stub had neither, because nothing created rows in it. Now that it is a
 * master with a form, it gets what every other master has: who made it, who
 * touched it last, and a delete that a category still pointing at the format
 * cannot silently erase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_formats', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');

            /*
             * Off by default. The prototype's toggle reads "Off — one colour
             * per item row", and a format that silently multiplied every row
             * into colour rows would change the shape of orders already using
             * it.
             */
            $table->boolean('allow_multiple_colours')->default(false)->after('status');

            // Printed at the bottom of every PO using this format.
            $table->text('delivery_details')->nullable()->after('allow_multiple_colours');
            $table->text('packing_details')->nullable()->after('delivery_details');

            $table->foreignId('created_by')->nullable()->after('packing_details')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();

            $table->softDeletes();
        });

        /*
         * The unit list. Stored as plain strings rather than a foreign key for
         * the reason above: there is no unit master to point at, and the client
         * asked for typing at master level.
         */
        Schema::create('document_format_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_format_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->tinyInteger('sort_order')->unsigned()->default(0);

            // Two "PCS" rows on one format is a data-entry slip, not a choice.
            $table->unique(['document_format_id', 'name']);
            $table->index(['document_format_id', 'sort_order']);
        });

        /*
         * The item table's columns.
         *
         * `key` is what downstream code will read to know which column it is
         * looking at — a PO renderer asks for "qty", not for "the seventh
         * column". Standard keys come from DocumentFormatColumn::STANDARD; a
         * custom column gets a slug derived from its label and is_custom set,
         * so the renderer knows there is no built-in value behind it.
         *
         * `is_enabled` rather than deleting the row: switching a standard
         * column off and back on should not lose the label the user typed, and
         * the prototype's preview shows every standard column in a fixed
         * order with some of them blank.
         */
        Schema::create('document_format_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_format_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('label', 60);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_custom')->default(false);

            /*
             * "image column hidden on screen · visible on print/PDF only" —
             * the prototype's preview note. A column can be part of the format
             * without taking width on a data-entry screen.
             */
            $table->boolean('print_only')->default(false);
            $table->tinyInteger('sort_order')->unsigned()->default(0);

            $table->unique(['document_format_id', 'key']);
            $table->index(['document_format_id', 'sort_order']);
        });

        /*
         * "Shown on print / PDF / Excel export only" — packing diagrams, label
         * samples, marking references. Paths on the public disk.
         */
        Schema::create('document_format_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_format_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('original_name', 160)->nullable();
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['document_format_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_format_images');
        Schema::dropIfExists('document_format_columns');
        Schema::dropIfExists('document_format_units');

        Schema::table('document_formats', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->dropColumn([
                'description',
                'allow_multiple_colours',
                'delivery_details',
                'packing_details',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });
    }
};
