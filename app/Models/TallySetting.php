<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TallySetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'host_url',
        'company_name',
        'sales_voucher_type',
        'debit_note_voucher_type',
        'sales_ledger',
        'igst_ledger',
        'job_work_ledger',
    ];

    protected $attributes = [
        'is_enabled'              => false,
        'host_url'                => 'http://127.0.0.1:9000',
        'sales_voucher_type'      => 'Sales',
        'debit_note_voucher_type' => 'Debit Note',
        'sales_ledger'            => 'Sales Accounts',
        'igst_ledger'             => 'IGST',
        'job_work_ledger'         => 'Job Work Charges',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }
}
