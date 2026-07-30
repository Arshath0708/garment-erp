<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lookup. Buyer sheet col N — destination port.
 */
class Port extends Model
{
    use Filterable;

    protected $fillable = ['country_id', 'code', 'name', 'type', 'status'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['code', 'name'];
    }

    /**
     * "London Port (GB)" — the country matters here. Several countries have a
     * port with the same name, and the dropdown is searched by typing.
     */
    protected function label(): Attribute
    {
        return Attribute::get(function () {
            $suffix = $this->country?->iso_code;

            return $suffix ? "{$this->name} ({$suffix})" : $this->name;
        });
    }
}
