<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Insurance (#16) option 1 — "cancel draft" — needs a cancelled status.
 * Also widens the old ENUM/CHECK column to a plain string so future
 * checklist statuses do not require another ALTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite ENUMs are CHECK constraints; rebuild the column as TEXT.
            Schema::table('export_document_checklists', function (Blueprint $table) {
                $table->string('status_tmp', 20)->default('pending');
            });

            DB::table('export_document_checklists')->update([
                'status_tmp' => DB::raw('status'),
            ]);

            Schema::table('export_document_checklists', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('export_document_checklists', function (Blueprint $table) {
                $table->renameColumn('status_tmp', 'status');
            });

            return;
        }

        Schema::table('export_document_checklists', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Irreversible on SQLite without reintroducing the CHECK list.
    }
};
