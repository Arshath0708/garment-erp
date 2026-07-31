<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup. Supplier sheet col I: "designation — search bar and option to add a
 * new one in the future".
 *
 * Read as "this list will grow", not "let the user type a new one inline" —
 * the same wording appears on incoterms, shipment methods and currencies, all
 * of which are seeded lookups edited from the Lookups screen under Settings.
 * Free-typing here would give three spellings of "Merchandiser" within a month.
 */
class Designation extends Model
{
    use Filterable;

    protected $fillable = ['name', 'status'];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }
}
