<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Markup Master — the margin we take on one supplier's goods for one buyer.
 *
 * The client's prototype states the arithmetic plainly, in three lines under
 * the two percentage fields:
 *
 *     CP + Markup%    = Client Price
 *     CP - Discount%  = Our Cost
 *     Client Price - Our Cost = Profit
 *
 * ## This settles an open question
 *
 * DATABASE_SCHEMA.md recorded a disagreement about the markup formula: our
 * notes computed `net_cost x (1 + m/100)` (100 at 12% -> 112) while an earlier
 * reading of the prototype suggested `total_cost / ((100 - m) / 100)` (-> 113.64).
 * The Markup Master screen says "CP + Markup% = Client Price" and captions the
 * field "Added on top of cost price -> client price". It is the first form,
 * and the second reading was wrong. Nothing computed either way yet, so
 * nothing needs correcting downstream — but the answer is now on record.
 *
 * ## Why the discount is not a column here
 *
 * The prototype's Discount % field is filled in for you and captioned
 * "Auto-filled from Supplier Master · edit there to change". It is the
 * supplier's negotiated discount (`suppliers.discount_percent`), shown on this
 * screen so both halves of the profit calculation are visible at once. Copying
 * it into a column would create a second place for it to be true, and the
 * caption says which place wins.
 *
 * ## One rule per supplier-buyer pair
 *
 * "Applied per CP at OC / PO entry" — when an order line names a supplier and
 * the order names a buyer, exactly one markup must answer. A unique index
 * enforces that rather than leaving the lookup to pick between duplicates.
 *
 * Consistent with every other master here, the index does not exclude
 * soft-deleted rows, and neither does the validation: a deleted rule keeps its
 * pair reserved. Restore it rather than recreating it — the alternative is
 * validation passing and the insert then failing on a duplicate key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markups', function (Blueprint $table) {
            $table->id();

            /*
             * restrictOnDelete on both sides: a markup with no supplier or no
             * buyer cannot be applied to anything, and MarkupService says so in
             * a sentence before the database says it in an error.
             */
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained()->restrictOnDelete();

            // "Auto-generated on record creation" — set by the service, shown
            // read-only on the form.
            $table->date('record_date');

            /*
             * 5,2 allows up to 999.99. A markup above 100% is unusual but not
             * impossible on a low-cost line, so the width does not cap what the
             * business may negotiate; validation decides the sensible range.
             */
            $table->decimal('markup_percent', 5, 2);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['supplier_id', 'buyer_id']);
            $table->index(['status', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markups');
    }
};
