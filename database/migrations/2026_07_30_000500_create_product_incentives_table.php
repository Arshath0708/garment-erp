<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Export incentive schemes per product — Product Master sheet columns L to U.
 *
 * The sheet lays these out as ten flat columns:
 *
 *   L  drawback %        M  cap value   N  calculated on
 *   O  rosctl % 1        P  rosctl % 2  Q  cap value   R  calculated on
 *   S  rodtep %          T  cap value   U  calculated on
 *
 * Stored as rows instead of columns. Three reasons:
 *
 *  1. A fourth scheme is a government announcement away. As columns that is an
 *     ALTER TABLE plus edits to the form, the validation, the list, the PDF and
 *     every export document. As rows it is one seeded enum value.
 *  2. The three blocks are the same shape — percent, cap, basis — so as columns
 *     they are the same three fields written out three times.
 *  3. Reporting "total incentive value on this shipment" is a sum over rows.
 *     Over columns it is a hand-written expression naming every scheme.
 *
 * RoSCTL is the reason for percent_2: it is the only scheme quoted as two
 * percentages. Drawback and RoDTEP use percent_1 and leave percent_2 null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('scheme', ['drawback', 'rosctl', 'rodtep']);

            $table->decimal('percent_1', 6, 3)->nullable();
            $table->decimal('percent_2', 6, 3)->nullable();   // RoSCTL only
            $table->decimal('cap_value', 12, 4)->nullable();

            // Sheet: "calculated on (this explains what my drawback is calculated on)"
            $table->foreignId('calculation_basis_id')->nullable()
                ->constrained('calculation_bases')->nullOnDelete();

            $table->timestamps();

            // One row per scheme per product — a product cannot have two
            // different drawback percentages.
            $table->unique(['product_id', 'scheme']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_incentives');
    }
};
