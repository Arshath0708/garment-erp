<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Change request #3 — up to 3 additional contacts on a Buyer, beyond the
 * primary contact_person/contact_designation_id/email/mobile columns on
 * `buyers`. Same shape as SupplierContact, minus `is_primary` — every row
 * here is an "additional" contact by definition.
 */
class BuyerContact extends Model
{
    protected $fillable = ['name', 'designation_id', 'mobile', 'email'];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
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
