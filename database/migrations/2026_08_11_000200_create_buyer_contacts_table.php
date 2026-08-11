<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #3 — Buyer: "Support a Secondary contact section that
 * allows up to 3 additional contacts per record."
 *
 * Mirrors `supplier_contacts` (see the 000200 create-suppliers migration).
 * The buyer's primary contact stays on `buyers` (contact_person,
 * contact_designation_id, email, mobile) — unlike Supplier, that shape
 * predates this change and downstream screens already read it, so it is left
 * alone. This table is only the additional people, capped at 3 by
 * BuyerRequest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();

            $table->index('buyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_contacts');
    }
};
