<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defect_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 30)->default('other'); // cutting | stitching | fabric | finishing | other
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('production_qc_checks', function (Blueprint $table) {
            $table->foreignId('defect_code_id')->nullable()->after('result')->constrained('defect_codes')->nullOnDelete();
            $table->text('capa_plan')->nullable()->after('notes');
            $table->date('capa_due_date')->nullable()->after('capa_plan');
            $table->string('capa_status', 20)->nullable()->after('capa_due_date'); // open | closed
            $table->timestamp('capa_closed_at')->nullable()->after('capa_status');
            $table->foreignId('capa_closed_by')->nullable()->after('capa_closed_at')->constrained('users')->nullOnDelete();
        });

        $now = now();
        $rows = [
            ['DF-ST-01', 'Broken / skipped stitch', 'stitching'],
            ['DF-ST-02', 'Uneven seam / open seam', 'stitching'],
            ['DF-ST-03', 'Needle hole / needle mark', 'stitching'],
            ['DF-CT-01', 'Wrong size cut', 'cutting'],
            ['DF-CT-02', 'Notch / shade mismatch panel', 'cutting'],
            ['DF-FB-01', 'Fabric hole / tear', 'fabric'],
            ['DF-FB-02', 'Oil / stain on fabric', 'fabric'],
            ['DF-FN-01', 'Loose thread / poor finishing', 'finishing'],
            ['DF-FN-02', 'Measurement out of tolerance', 'finishing'],
            ['DF-OT-01', 'Other / unspecified', 'other'],
        ];

        foreach ($rows as [$code, $name, $category]) {
            DB::table('defect_codes')->insert([
                'code'       => $code,
                'name'       => $name,
                'category'   => $category,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('production_qc_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('capa_closed_by');
            $table->dropConstrainedForeignId('defect_code_id');
            $table->dropColumn(['capa_plan', 'capa_due_date', 'capa_status', 'capa_closed_at']);
        });

        Schema::dropIfExists('defect_codes');
    }
};
