<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #7, second pass — a hidden preset list for the Markup
 * form's "Default Markup" dropdown. The first pass built this as a full
 * master (its own menu item, its own create/edit screens); that was asked to
 * be removed. This is the same idea scaled back to what was actually
 * wanted: a seeded table with no screen of its own — presets are changed by
 * editing DefaultMarkupSeeder and re-running it, not through the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_markups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->decimal('markup_percent', 5, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_markups');
    }
};
