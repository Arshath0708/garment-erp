<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * State and City lookups, and the switch of `buyers.state` / `buyers.city` from
 * free text to foreign keys.
 *
 * The Buyer sheet draws cols J and K as text boxes, and the 000800 migration
 * kept them that way on the grounds that nothing downstream filters or totals
 * on a city. That was true of the master screen alone; it stops being true the
 * moment the client asked for the three to cascade. A dependent dropdown needs
 * a parent to hang off, and "London" typed under country "GB" cannot be
 * validated, deduplicated or narrowed against anything.
 *
 * Same reasoning as `countries` and `ports` in 000600: these print on export
 * documents, where "Tirupur" / "Tiruppur" / "TIRUPPUR" typed across three
 * buyers becomes three cities on three invoices.
 *
 * The old string columns are dropped rather than kept alongside. `buyers` was
 * created in this same unreleased batch and holds no production data, so there
 * is nothing to migrate across — carrying a dead `city` string would just be a
 * second place for the answer to live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 10)->nullable();        // TN, CA, NRW
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Two countries may both have a "Georgia"; within one they may not.
            $table->unique(['country_id', 'name']);
            // The cascade endpoint filters on exactly this.
            $table->index(['country_id', 'status']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['state_id', 'name']);
            $table->index(['state_id', 'status']);
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn(['city', 'state']);
        });

        Schema::table('buyers', function (Blueprint $table) {
            // nullOnDelete, not cascade: losing a city must not delete the buyer.
            $table->foreignId('state_id')->nullable()->after('address')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('state_id');
            $table->string('city', 80)->nullable()->after('address');
            $table->string('state', 80)->nullable()->after('city');
        });

        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
    }
};
