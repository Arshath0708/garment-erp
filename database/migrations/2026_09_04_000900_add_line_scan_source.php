<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_line_outputs', function (Blueprint $table) {
            $table->string('source', 20)->default('desk')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('production_line_outputs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
