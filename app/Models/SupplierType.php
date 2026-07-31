<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup. Supplier sheet col D: "supplier type (composition) give option to add
 * more in the future".
 *
 * `is_registered` is what col E hangs off — "GST Number (only if the registered
 * option is chosen)". Keeping the flag on the row means a type the client adds
 * later declares for itself whether it carries a GST number, instead of the
 * rule needing a hard-coded list of names.
 */
class SupplierType extends Model
{
    use Filterable;

    /** Seeded codes referenced by name in tests and seeders. */
    public const UNREGISTERED = 'unregistered';

    public const REGISTERED_REGULAR = 'registered_regular';

    public const REGISTERED_COMPOSITION = 'registered_composition';

    protected $fillable = ['code', 'name', 'is_registered', 'status'];

    protected function casts(): array
    {
        return ['is_registered' => 'boolean'];
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['code', 'name'];
    }
}
