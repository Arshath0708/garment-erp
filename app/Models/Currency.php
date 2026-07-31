<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Buyer sheet col T: "currency of payment — drop down menu to choose
 * and add more in the future".
 */
class Currency extends Model
{
    use Filterable;

    protected $fillable = ['iso_code', 'name', 'symbol', 'status'];

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['iso_code', 'name'];
    }

    /** "USD — US Dollar". */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->iso_code} — {$this->name}");
    }
}
