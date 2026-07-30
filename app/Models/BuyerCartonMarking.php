<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One printed line of a buyer's carton / shipping mark — Buyer sheet col X.
 *
 * Rows rather than five columns on `buyers`: the sheet draws five lines but
 * asks for the "choice to add more too", and a sixth line should not be a
 * migration. `line_no` is the print order.
 */
class BuyerCartonMarking extends Model
{
    protected $fillable = ['buyer_id', 'line_no', 'label', 'value', 'is_required'];

    protected function casts(): array
    {
        return [
            'line_no'     => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }
}
