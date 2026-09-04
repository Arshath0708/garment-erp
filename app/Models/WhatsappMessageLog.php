<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessageLog extends Model
{
    public const SOURCE_PO = 'purchase_order';

    public const SOURCE_TNA = 'tna_step';

    public const STATUSES = [
        'sent' => 'Sent via API',
        'opened' => 'Opened WhatsApp',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'source_type',
        'source_id',
        'to_digits',
        'body',
        'channel',
        'status',
        'error_message',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
