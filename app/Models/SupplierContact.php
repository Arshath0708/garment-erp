<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Supplier sheet cols H–K (the person on the main form, `is_primary`) and col L
 * ("other contact persons — name, designation, phone number need to come again
 * to be filled").
 *
 * One table for both, so anything downstream that has to reach a supplier reads
 * one list rather than four columns plus a table.
 */
class SupplierContact extends Model
{
    protected $fillable = ['name', 'designation_id', 'mobile', 'email', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /** "Ramesh — Merchandiser", or just the name where no designation is set. */
    protected function label(): Attribute
    {
        return Attribute::get(
            fn () => $this->designation ? "{$this->name} — {$this->designation->name}" : $this->name
        );
    }
}
