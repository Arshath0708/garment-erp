<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyPostLog extends Model
{
    public const SOURCE_EXPORT = 'export_document';
    public const SOURCE_DEBIT = 'debit_note';

    public const STATUSES = [
        'downloaded' => 'XML downloaded',
        'posted'     => 'Posted to Tally',
        'failed'     => 'Failed',
    ];

    protected $fillable = [
        'source_type',
        'source_id',
        'voucher_type',
        'voucher_number',
        'status',
        'request_xml',
        'response_body',
        'error_message',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
