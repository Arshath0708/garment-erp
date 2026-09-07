<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('kind', 32)->default('fabric'); // fabric | finished | other
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('lot_no', 80);
            $table->decimal('qty_on_hand', 14, 3)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->foreignId('inward_entry_item_id')->nullable()->constrained('inward_entry_items')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'lot_no']);
            $table->index(['warehouse_id', 'product_id']);
        });

        $now = now();
        DB::table('warehouses')->insert([
            [
                'code'       => 'MAIN',
                'name'       => 'Main Fabric Godown',
                'kind'       => 'fabric',
                'is_active'  => true,
                'remarks'    => 'Default fabric / trims stores.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'       => 'FG',
                'name'       => 'Finished Goods',
                'kind'       => 'finished',
                'is_active'  => true,
                'remarks'    => 'Packed garments ready for dispatch.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('warehouses');
    }
};
