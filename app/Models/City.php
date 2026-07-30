<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lookup. Buyer sheet col J, narrowed by the selected state.
 */
class City extends Model
{
    use Filterable;

    protected $fillable = ['state_id', 'name', 'status'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
