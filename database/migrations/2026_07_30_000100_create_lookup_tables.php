<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dropdown sources for the Category and Product masters.
 *
 * Every one of these exists because a master sheet asked for a dropdown "with
 * an option to add more in the future". That phrase is the whole reason they
 * are tables and not PHP enums — the client expects to add a GST rate or a
 * unit without a developer.
 *
 * They are not business masters and get no sidebar entry of their own; they
 * belong on the single Lookups screen under Settings. Until that screen is
 * built they are populated by LookupSeeder.
 *
 * There is deliberately NO `units` table. The client cancelled the Unit Master
 * ("I don't see a need for the unit master") and set the rule: manual typing at
 * master level, dropdowns everywhere after it. So unit is typed on the Product
 * master and every downstream screen reads it from the selected product.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Product sheet col J: "drop down menu (AA and AB with an option to add in the future)"
        Schema::create('price_bands', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();          // AA, AB
            $table->string('name', 60);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Product sheet col K: "menu (different rates with option to add in the
        // future with a search bar)". Stored as a rate, not a percentage string,
        // so tax maths never parses text.
        Schema::create('gst_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 5, 2)->unique();       // 0.00, 5.00, 12.00, 18.00
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Product sheet cols N, R, U: "calculated on (this explains what my
        // drawback / rosctl / rodtep is calculated on)" — a dropdown with a
        // search bar and the ability to add more later.
        Schema::create('calculation_bases', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();          // FOB Value, Quantity, Net Weight, Sq. Metre
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Category sheet col F: "Po format linked — drop down with search bar to
        // look for particular formats". The PO Format master is a later phase;
        // the table exists now so the FK on categories is real rather than a
        // loose integer that has to be migrated again later.
        Schema::create('document_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('module', 40)->default('po');   // po, oc, quotation, invoice, packing_list
            $table->string('blade_view', 160)->nullable(); // exports.po.standard
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_formats');
        Schema::dropIfExists('calculation_bases');
        Schema::dropIfExists('gst_rates');
        Schema::dropIfExists('price_bands');
    }
};
