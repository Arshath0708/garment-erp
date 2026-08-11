<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #7 revert — the Default Markup preset master was built,
 * then the client asked for it to be removed entirely; the Markup form goes
 * back to manual entry only. Reverses the 2026_08_11_000300 create migration
 * rather than editing it, since that one already ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('default_markups');
    }

    public function down(): void
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
};
