<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Buyer sheet col Q.
 *
 * `applies_to` exists because the Supplier master has its own payment-terms
 * column. "Advance" is offered on both sides, but the buyer-side and
 * supplier-side lists are not the same list.
 */
class PaymentTerm extends Model
{
    use Filterable;

    protected $fillable = ['name', 'days', 'has_split', 'applies_to', 'status'];

    protected function casts(): array
    {
        return [
            'days'      => 'integer',
            'has_split' => 'boolean',
        ];
    }

    /**
     * Terms offered on one side of the business, including those marked "both".
     */
    public function scopeForSide(Builder $query, string $side): Builder
    {
        return $query->whereIn('applies_to', [$side, 'both']);
    }
}
