<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Our own company's details — needed to print an export invoice (name,
 * address, GSTIN, IEC, bank) but never modeled anywhere before now. A
 * singleton: CompanyProfile::current() always returns row id 1, created on
 * first read if missing. See config/permissions.php's Administration group
 * docblock — this table is exactly what that comment anticipated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('tagline')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('gstin')->nullable();
            $table->string('iec_code')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('bank_swift')->nullable();

            $table->string('signatory_name')->nullable();
            $table->string('signatory_designation')->nullable();

            $table->string('logo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile');
    }
};
