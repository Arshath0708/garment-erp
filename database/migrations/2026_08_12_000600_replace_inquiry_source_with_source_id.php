<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #8, second pass — replaces the fixed `source` string column
 * (validated against Inquiry::SOURCES) with a `source_id` FK into the new
 * `inquiry_sources` lookup, and drops `source_other` — a free-text "please
 * specify" box is redundant once typing a new source adds it to the list
 * for good.
 *
 * Existing rows are backfilled by matching their old key to the
 * correspondingly-named row the previous migration just seeded, so no
 * inquiry loses its recorded source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreignId('source_id')->nullable()->after('source_other')
                ->constrained('inquiry_sources')->restrictOnDelete();
        });

        $ids = DB::table('inquiry_sources')->pluck('id', 'name');

        $map = [
            'direct'     => 'Direct',
            'agent'      => 'Through Agent',
            'referral'   => 'Referral',
            'exhibition' => 'Exhibition',
            'website'    => 'Website / Email',
            'other'      => 'Other',
        ];

        foreach ($map as $oldKey => $name) {
            if (! isset($ids[$name])) {
                continue;
            }

            DB::table('inquiries')->where('source', $oldKey)->update(['source_id' => $ids[$name]]);
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_other']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('source')->nullable()->after('buyer_ref');
            $table->string('source_other', 150)->nullable()->after('source');
        });

        $names = DB::table('inquiry_sources')->pluck('name', 'id');

        $map = [
            'Direct' => 'direct', 'Through Agent' => 'agent', 'Referral' => 'referral',
            'Exhibition' => 'exhibition', 'Website / Email' => 'website', 'Other' => 'other',
        ];

        foreach (DB::table('inquiries')->whereNotNull('source_id')->get(['id', 'source_id']) as $row) {
            $name = $names[$row->source_id] ?? null;
            $key = $map[$name] ?? 'other';

            DB::table('inquiries')->where('id', $row->id)->update(['source' => $key]);
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_id');
        });
    }
};
