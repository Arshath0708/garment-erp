<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('host_url')->default('http://127.0.0.1:9000');
            $table->string('company_name')->nullable();
            $table->string('sales_voucher_type')->default('Sales');
            $table->string('debit_note_voucher_type')->default('Debit Note');
            $table->string('sales_ledger')->default('Sales Accounts');
            $table->string('igst_ledger')->default('IGST');
            $table->string('job_work_ledger')->default('Job Work Charges');
            $table->timestamps();
        });

        Schema::create('tally_post_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->string('voucher_type', 40);
            $table->string('voucher_number')->nullable();
            $table->string('status', 20);
            $table->longText('request_xml');
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'voucher_type']);
            $table->index('status');
        });

        Schema::table('export_documents', function (Blueprint $table) {
            $table->string('gst_irn', 64)->nullable()->after('invoice_no');
            $table->string('gst_ack_no', 64)->nullable()->after('gst_irn');
            $table->date('gst_ack_date')->nullable()->after('gst_ack_no');
        });
    }

    public function down(): void
    {
        Schema::table('export_documents', function (Blueprint $table) {
            $table->dropColumn(['gst_irn', 'gst_ack_no', 'gst_ack_date']);
        });
        Schema::dropIfExists('tally_post_logs');
        Schema::dropIfExists('tally_settings');
    }
};
