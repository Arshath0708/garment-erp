<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Buyer sheet col S: "drop down menu to choose and add more in the
 * future".
 */
class ShipmentMethod extends Model
{
    use Filterable;

    protected $fillable = ['name', 'status'];
}
