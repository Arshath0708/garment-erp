<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One dated line of buyer communication. Append-only — no updated_at, since
 * an entry is never edited, only added or (as part of a full inquiry resave)
 * dropped.
 */
class InquiryFollowUp extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'follow_up_date',
        'comment',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['follow_up_date' => 'date'];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
