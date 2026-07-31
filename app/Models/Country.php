<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup. Buyer sheet col L.
 *
 * A table rather than a typed string because the country prints on export
 * documents — "UK", "U.K." and "United Kingdom" typed across three buyers
 * become three countries on three invoices.
 */
class Country extends Model
{
    use Filterable;

    protected $fillable = ['iso_code', 'name', 'dial_code', 'status'];

    public function ports(): HasMany
    {
        return $this->hasMany(Port::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['iso_code', 'name'];
    }

    /** "United Kingdom (GB)". An accessor so dropdowns can pluck('label', 'id'). */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->name} ({$this->iso_code})");
    }
}
