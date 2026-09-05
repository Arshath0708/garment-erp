<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('phone_number_id')->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->string('graph_version', 20)->default('v21.0');
            $table->string('country_code', 5)->default('91');
            $table->timestamps();
        });

        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->string('to_digits', 20);
            $table->text('body');
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('whatsapp_settings');
    }
};
