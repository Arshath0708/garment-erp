<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedInteger('printing_qty')->default(0)->after('cutting_qty');
            $table->json('size_breakdown')->nullable()->after('dispatch_qty');
            $table->string('job_work_type')->default('in_house')->after('notes');
            $table->foreignId('jobber_id')->nullable()->after('job_work_type')->constrained('suppliers')->nullOnDelete();
            $table->string('place_of_supply')->nullable()->after('jobber_id');
            $table->string('vehicle_no')->nullable()->after('place_of_supply');
            $table->string('driver_name')->nullable()->after('vehicle_no');
            $table->string('challan_no')->nullable()->after('driver_name');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jobber_id');
            $table->dropColumn([
                'printing_qty',
                'size_breakdown',
                'job_work_type',
                'place_of_supply',
                'vehicle_no',
                'driver_name',
                'challan_no',
            ]);
        });
    }
};
