<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Product sheet col K: "different rates with option to add in the future".
 *
 * Stored as a number so tax maths never parses a label.
 */
class GstRate extends Model
{
    use Filterable;

    protected $fillable = ['rate', 'status'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:2'];
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['rate'];
    }

    /** "5%" rather than "5.00%". An accessor so pluck() can find it. */
    protected function label(): Attribute
    {
        return Attribute::get(
            fn () => rtrim(rtrim(number_format((float) $this->rate, 2), '0'), '.').'%'
        );
    }
}
