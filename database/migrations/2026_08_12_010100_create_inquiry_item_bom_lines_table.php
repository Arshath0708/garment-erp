<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot of Product BOM onto an inquiry line — same discipline as
        // copying buyer agent rates: saved inquiry rows must not drift when
        // Product Master BOM changes later.
        Schema::create('inquiry_item_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('component_name', 200);
            $table->decimal('qty', 12, 4)->default(1);
            $table->string('unit', 20)->nullable();
            $table->boolean('is_custom')->default(true);
            $table->string('remarks', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_item_bom_lines');
    }
};
