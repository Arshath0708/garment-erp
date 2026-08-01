<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A category links to **several** order formats, not one.
 *
 * The Category sheet drew col F as a single "Po format linked" dropdown, and
 * the 000300 migration built `categories.po_format_id` to match. The client's
 * prototype disagrees: its Category Master holds a `formats` array, the form
 * adds them one at a time as removable tags, and the note under the picker
 * reads "each will be available when creating a PO under this category".
 *
 * That is a different statement about the business. One format per category
 * means the format is decided the moment the category is — nobody chooses. A
 * set means the category narrows the choice and the order screen makes it.
 *
 * ## What this does not answer
 *
 * DATABASE_SCHEMA.md records an open question: a purchase order can contain
 * products from more than one category, so if those categories link to
 * different formats, which one prints? A set per category makes that question
 * larger, not smaller — it is still for the client to answer, and it is on the
 * list for the next call. What this migration settles is only the cardinality
 * between a category and a format, which the prototype states plainly.
 *
 * `po_format_id` is dropped after its value is carried into the pivot. Keeping
 * it as "the default format" was considered and rejected: unlike the buyer's
 * currency, nothing downstream needs a single answer today — the PO screen does
 * not exist yet — and a column nothing reads is a column that drifts.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * restrictOnDelete on the format side, matching buyer_category and
         * supplier_category: a format a category depends on cannot be deleted
         * out from under it, and CategoryService gets to say so in a sentence
         * before the database says it in an error.
         */
        Schema::create('category_format', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_format_id')->constrained('document_formats')->restrictOnDelete();

            $table->unique(['category_id', 'document_format_id']);
        });

        DB::table('categories')
            ->whereNotNull('po_format_id')
            ->orderBy('id')
            ->select('id', 'po_format_id')
            ->chunk(200, function ($categories) {
                DB::table('category_format')->insertOrIgnore(
                    $categories->map(fn ($category) => [
                        'category_id'        => $category->id,
                        'document_format_id' => $category->po_format_id,
                    ])->all()
                );
            });

        Schema::table('categories', function (Blueprint $table) {
            // Named explicitly: Laravel's convention for this constraint is
            // categories_po_format_id_foreign, and dropColumn alone leaves it
            // behind on MySQL.
            $table->dropForeign(['po_format_id']);
            $table->dropColumn('po_format_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('po_format_id')->nullable()
                ->after('description')
                ->constrained('document_formats')->nullOnDelete();
        });

        /*
         * A rollback cannot keep a set in a single column, so the lowest-id
         * format wins and the rest are lost. Deterministic rather than
         * arbitrary — re-running up() then produces the same single link.
         */
        DB::table('category_format')
            ->select('category_id', DB::raw('MIN(document_format_id) as format_id'))
            ->groupBy('category_id')
            ->orderBy('category_id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('categories')
                        ->where('id', $row->category_id)
                        ->update(['po_format_id' => $row->format_id]);
                }
            });

        Schema::dropIfExists('category_format');
    }
};
