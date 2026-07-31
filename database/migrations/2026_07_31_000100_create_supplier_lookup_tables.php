<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two lookups the Supplier Master sheet asks to be extensible.
 *
 * | Col | Sheet note                                                    |
 * |-----|---------------------------------------------------------------|
 * | D   | "supplier type (composition) give option to add more in the future" |
 * | I   | "designation — search bar and option to add a new one in the future" |
 *
 * DATABASE_SCHEMA.md §5 draws `supplier_type` as an enum and says "consider a
 * lookup table if that happens". It has already happened — the sheet asks for
 * it in the column note — and every other "add more in the future" column in
 * this project (price bands, GST rates, incoterms, shipment methods,
 * currencies) is a table. An enum would make the client's stated requirement a
 * migration, which is the thing they were asking not to need.
 *
 * `is_registered` is the reason this is not a plain name list: Supplier sheet
 * col E is "GST Number (only if the registered option is chosen)". Something
 * has to say which of the types count as registered, and putting that flag on
 * the row means a type added later declares it for itself rather than having
 * to be added to a hard-coded list in the request class.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_types', function (Blueprint $table) {
            $table->id();

            // Stable handle for seeders and any future code that has to name a
            // specific type. The name is the client's to rename; this is not.
            $table->string('code', 30)->unique();
            $table->string('name', 80);

            // Drives the conditional GST field — see col E.
            $table->boolean('is_registered')->default(false);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
        Schema::dropIfExists('supplier_types');
    }
};
