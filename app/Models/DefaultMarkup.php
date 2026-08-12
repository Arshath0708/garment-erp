<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Change request #7 — a named markup-percent preset for the Markup form's
 * "Default Markup" dropdown. Seeded, not managed through a screen — see
 * DefaultMarkupSeeder. Deliberately without Filterable/HasAuditColumns: those
 * exist for models with an index/create/edit screen, and this one has none.
 */
class DefaultMarkup extends Model
{
    protected $fillable = [
        'name',
        'markup_percent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'markup_percent' => 'decimal:2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
