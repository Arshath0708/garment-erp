<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add comments column to Buyers, Suppliers, Products, and Agents tables.
     */
    public function up(): void
    {
        if (Schema::hasTable('buyers') && ! Schema::hasColumn('buyers', 'comments')) {
            Schema::table('buyers', function (Blueprint $table) {
                $table->text('comments')->nullable()->after('remarks');
            });
        }

        if (Schema::hasTable('suppliers') && ! Schema::hasColumn('suppliers', 'comments')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->text('comments')->nullable()->after('remarks');
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'comments')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('comments')->nullable()->after('remarks');
            });
        }

        if (Schema::hasTable('agents') && ! Schema::hasColumn('agents', 'comments')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->text('comments')->nullable()->after('remarks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('buyers') && Schema::hasColumn('buyers', 'comments')) {
            Schema::table('buyers', function (Blueprint $table) {
                $table->dropColumn('comments');
            });
        }

        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'comments')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('comments');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'comments')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('comments');
            });
        }

        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'comments')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('comments');
            });
        }
    }
};
