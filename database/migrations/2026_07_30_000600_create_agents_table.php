<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->enum('agent_type', ['supplier', 'buyer', 'jobber']);
            $table->string('name', 200);
            $table->string('display_code', 5)->unique(); // manual textbox, max 5 characters, unique

            // Optional Commission settings
            $table->foreignId('calculation_basis_id')->nullable()->constrained('calculation_bases')->nullOnDelete();
            $table->decimal('commission_rate', 8, 2)->nullable(); // Reserved for future use

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('agent_type');
            $table->index('status');
        });

        Schema::create('agent_category', function (Blueprint $table) {
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['agent_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_category');
        Schema::dropIfExists('agents');
    }
};
