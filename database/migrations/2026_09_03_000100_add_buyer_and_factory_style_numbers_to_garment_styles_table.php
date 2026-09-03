<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garment_styles', function (Blueprint $table) {
            $table->string('buyer_style_no', 100)->nullable()->after('style_number');
            $table->string('factory_style_no', 100)->nullable()->after('buyer_style_no');
        });
    }

    public function down(): void
    {
        Schema::table('garment_styles', function (Blueprint $table) {
            $table->dropColumn(['buyer_style_no', 'factory_style_no']);
        });
    }
};
