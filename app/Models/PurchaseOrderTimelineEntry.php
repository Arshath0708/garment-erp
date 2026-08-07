<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One dated line of the PO's delivery timeline. Plain hand-entered rows for
 * now — the prototype describes Goods Inward auto-populating this list once
 * that module exists, which it does not yet.
 */
class PurchaseOrderTimelineEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entry_date',
        'note',
        'qty',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'qty'        => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
