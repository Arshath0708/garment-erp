<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Change request #8, second pass — Source turned into a real quick-add
 * lookup instead of the fixed Inquiry::SOURCES list + a free-text "Other"
 * box. Seeded here with the same six labels that list already had, so no
 * inquiry's source disappears from under it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        $now = now();

        DB::table('inquiry_sources')->insert(
            collect(['Direct', 'Through Agent', 'Referral', 'Exhibition', 'Website / Email', 'Other'])
                ->map(fn (string $name) => [
                    'name' => $name, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_sources');
    }
};
