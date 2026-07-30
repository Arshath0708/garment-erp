<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Product sheet col J: "AA and AB with an option to add in the future".
 */
class PriceBand extends Model
{
    use Filterable;

    protected $fillable = ['code', 'name', 'status'];

    /** An accessor, not a method: dropdowns use pluck('label', 'id'). */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->code} — {$this->name}");
    }
}
