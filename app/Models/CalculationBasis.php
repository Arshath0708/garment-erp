<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Product sheet cols N, R and U: "calculated on (this explains what my
 * drawback / rosctl / rodtep is calculated on)".
 */
class CalculationBasis extends Model
{
    use Filterable;

    protected $table = 'calculation_bases';

    protected $fillable = ['name', 'status'];
}
