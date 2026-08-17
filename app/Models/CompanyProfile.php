<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Singleton — one row (id 1), our own company's details. Read via
 * CompanyProfile::current() so callers never have to null-check; export
 * documents (invoice, bank docs, etc.) print from this rather than
 * hard-coded values.
 */
class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    protected $fillable = [
        'company_name',
        'tagline',
        'address',
        'phone',
        'email',
        'gstin',
        'iec_code',
        'bank_name',
        'bank_account_number',
        'bank_ifsc',
        'bank_swift',
        'signatory_name',
        'signatory_designation',
        'logo_path',
    ];

    /**
     * There is only ever one row — not pinned to id 1 (mass assignment
     * can't set 'id' since it's deliberately absent from $fillable), just
     * "the first one, or create it if the table is empty."
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['company_name' => 'Company Name']);
    }

    public function hasLogo(): bool
    {
        return filled($this->logo_path);
    }

    public function logoUrl(): ?string
    {
        return $this->hasLogo() ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
