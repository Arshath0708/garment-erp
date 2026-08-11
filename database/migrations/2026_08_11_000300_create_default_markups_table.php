<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #7 — Markup: "Add a Default markup option, selectable from
 * a dropdown (in addition to manual entry)."
 *
 * A small lookup master, same shape as `fob_values` — a named preset
 * (e.g. "Standard 10%") carrying the percentage it fills in. Picking one on
 * the Markup form fills `markup_percent`; the field stays editable
 * afterwards, same as every other "auto-filled, edit to change" field on
 * that form.
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
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_markups');
    }
};
