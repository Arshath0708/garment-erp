<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product master — Product Master sheet, columns A to AA.
 *
 * | Col | Field                        | Sheet note                                   |
 * |-----|------------------------------|----------------------------------------------|
 * | A   | Category                     | dropdown -> categories                       |
 * | B   | Item Group code              | "incase a code is already taken I should get an alert here" |
 * | C   | Product Name                 | "incase a name is already taken I should get an alert here" |
 * | D   | product name as per export document | text box                               |
 * | E   | barcode                      | "needs to have text and number allowed"      |
 * | F   | Unit (po and oc)             | typed — Unit Master cancelled by the client  |
 * | G   | Unit (export docs)           | typed — same                                 |
 * | H   | HSN Code                     | text box (NOT a dropdown on this sheet)      |
 * | I   | drawback sr no               | text box                                     |
 * | J   | price band                   | dropdown (AA / AB, add more in the future)   |
 * | K   | GST %                        | menu of rates, add more in the future        |
 * | L–U | drawback / rosctl / rodtep   | -> product_incentives, see that migration    |
 * | V   | Fabric length in mtrs        | text box                                     |
 * | W   | fabric width in inch         | text box                                     |
 * | X   | calculated sq mtrs / unit    | "autocalculated based on inputted values"    |
 * | Y   | Description                  | text box                                     |
 * | Z   | Status                       | dropdown                                     |
 * | AA  | Remarks                      | text box                                     |
 *
 * HSN Code is a plain text box on this sheet, so it is a string column here and
 * not a foreign key to an HSN master. If HSN ever needs a default GST rate or a
 * description attached, that becomes a lookup table and this becomes an FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            // Both carry a "tell me if it is already taken" note on the sheet.
            // The unique index is the guard; the live check on the form is only
            // a convenience — two users typing PRD001 at the same moment both
            // pass the AJAX check and only the index stops the second one.
            $table->string('item_group_code', 20)->unique();
            $table->string('name', 200)->unique();

            $table->string('name_on_export_document', 255)->nullable();
            $table->string('barcode', 60)->nullable();

            // Typed, not picked. The sheet annotated these as dropdowns, but the
            // client later cancelled the Unit Master and ruled: "textboxes and
            // manual feeding only at the master levels, and after that only drop
            // down menus". So the unit is entered here once, and every screen
            // downstream reads it from the selected product rather than asking
            // again. The unit printed on a PO is not always the unit declared on
            // the export document, hence two fields.
            $table->string('unit_po', 20)->nullable();
            $table->string('unit_export', 20)->nullable();

            $table->string('hsn_code', 12)->nullable();
            $table->string('drawback_sr_no', 20)->nullable();

            $table->foreignId('price_band_id')->nullable()->constrained('price_bands')->nullOnDelete();
            $table->foreignId('gst_rate_id')->nullable()->constrained('gst_rates')->nullOnDelete();

            // Sheet: "sq metre calculation (this is done for fabrics and sarees,
            // it a custom requirement which will be connected ahead.)"
            $table->decimal('fabric_length_mtr', 10, 3)->nullable();
            $table->decimal('fabric_width_inch', 10, 3)->nullable();

            // Column X is "autocalculated based on inputted values". A stored
            // generated column keeps the 39.3701 divisor in exactly one place —
            // if it were computed in PHP the list screen, the PDF and the export
            // document would each need their own copy of the formula.
            $table->decimal('sq_mtr_per_unit', 12, 4)
                ->storedAs('(fabric_length_mtr * fabric_width_inch) / 39.3701')
                ->nullable();

            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
