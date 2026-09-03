<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNote extends Model
{
    use HasAuditColumns;

    public const SOURCE_JOB_WORK = 'job_work_voucher';

    public const STATUSES = [
        'draft'    => 'Draft',
        'issued'   => 'Issued',
    ];

    public const REASONS = [
        'job_work_damage' => 'Job-work damage',
        'qty_short'       => 'Quantity short',
        'rate_difference' => 'Rate difference',
        'other'           => 'Other',
    ];

    protected $fillable = [
        'note_date',
        'supplier_id',
        'source_type',
        'source_id',
        'amount',
        'qty',
        'reason',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'note_date' => 'date',
            'amount'    => 'decimal:2',
            'qty'       => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function jobWorkVoucher(): BelongsTo
    {
        return $this->belongsTo(JobWorkVoucher::class, 'source_id');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ($this->reason ?: '—');
    }
}
