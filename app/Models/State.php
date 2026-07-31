<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup. Buyer sheet col K, narrowed by the selected country.
 */
class State extends Model
{
    use Filterable;

    protected $fillable = ['country_id', 'name', 'code', 'status'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['name', 'code'];
    }
}
