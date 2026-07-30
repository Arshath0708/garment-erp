<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Buyer sheet col R: "drop down menu with search bar, choice to add
 * more too".
 */
class Incoterm extends Model
{
    use Filterable;

    protected $fillable = ['code', 'name', 'description', 'status'];

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['code', 'name'];
    }

    /** "FOB — Free On Board". */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->code} — {$this->name}");
    }
}
