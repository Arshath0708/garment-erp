<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holds one value per custom column the item's Order Format defines
     * (DocumentFormatColumn::is_custom) — keyed by the column's key, same as
     * DocumentFormatColumn::$key. There is no way to know the set of custom
     * columns in advance (every format can define its own), so this is a
     * JSON bag rather than one column per possible custom field.
     */
    public function up(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->json('custom_values')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->dropColumn('custom_values');
        });
    }
};
