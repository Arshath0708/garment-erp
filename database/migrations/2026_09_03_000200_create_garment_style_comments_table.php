<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garment_style_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_style_id')->constrained('garment_styles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->text('comment');
            $table->timestamps();

            $table->index('garment_style_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garment_style_comments');
    }
};
