<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Change request #8, second pass — where an inquiry came from, as a
 * quick-add lookup rather than a fixed list. Typing a name not already
 * here (see InquiryController::storeSource()) adds it, same pattern as
 * Designation/PaymentTerm on the Buyer form.
 */
class InquirySource extends Model
{
    use Filterable;

    protected $fillable = ['name', 'status'];

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
