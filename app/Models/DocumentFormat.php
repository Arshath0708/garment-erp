<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup. Category sheet col F: "Po format linked".
 *
 * The PO Format master is a later phase — this table exists now so the
 * category FK is real rather than a loose integer migrated again later.
 */
class DocumentFormat extends Model
{
    use Filterable;

    protected $fillable = ['name', 'module', 'blade_view', 'status'];
}
