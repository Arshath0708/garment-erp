<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dropdown sources the Buyer master needs — DATABASE_SCHEMA.md §1.
 *
 * Same reasoning as the Category/Product lookups in 000100: every one of these
 * is a table rather than a PHP enum because the Buyer sheet annotates the
 * column "with an option to add more in the future". The client expects to add
 * an incoterm or a shipment method without a developer.
 *
 * They are not business masters and get no sidebar entry — they belong on the
 * single Lookups screen under Settings. Until that exists, LookupSeeder fills
 * them.
 *
 * | Buyer sheet col | Field             | Table             |
 * |-----------------|-------------------|-------------------|
 * | L               | Country           | countries         |
 * | N               | Port              | ports             |
 * | Q               | Payment Terms     | payment_terms     |
 * | R               | Inco terms        | incoterms         |
 * | S               | Shipment methods  | shipment_methods  |
 * | T               | Currency of payment | currencies      |
 *
 * City and State stay free-text `string` columns on `buyers` rather than
 * becoming tables of their own. The sheet marks both "text box", and a state
 * list per country is a large amount of seed data for a field nothing filters,
 * sorts or totals on.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Buyer sheet col L. ISO 3166-1 alpha-2 as the natural key so a country
        // can be matched against an external list later without a name compare.
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso_code', 2)->unique();          // IN, GB, US
            $table->string('name', 100);
            $table->string('dial_code', 10)->nullable();    // +91
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Buyer sheet col T: "drop down menu to choose and add more in the future".
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('iso_code', 3)->unique();          // INR, USD, GBP, EUR
            $table->string('name', 60);
            $table->string('symbol', 10)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Buyer sheet col N. Destination port, so it hangs off a country —
        // "London Port" is only meaningful once you know which London.
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20)->nullable();         // UN/LOCODE, e.g. INMAA
            $table->string('name', 120);
            $table->enum('type', ['sea', 'air', 'land'])->default('sea');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['country_id', 'status']);
        });

        // Buyer sheet col R: "drop down menu with search bar, choice to add more too".
        Schema::create('incoterms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();           // FOB, CIF, CFR, EXW
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Buyer sheet col Q. `applies_to` exists because the Supplier master has
        // its own payment-terms column: "Advance" is offered to both sides, but
        // buyer-side and supplier-side term lists are not the same list.
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();           // "30 Days", "Advance"
            $table->smallInteger('days')->nullable();       // 30, 0 — for due-date maths later
            $table->enum('applies_to', ['buyer', 'supplier', 'both'])->default('both');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['applies_to', 'status']);
        });

        // Buyer sheet col S: "drop down menu to choose and add more in the future".
        Schema::create('shipment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();           // Sea, Air, Courier, Land
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_methods');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('incoterms');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
    }
};
