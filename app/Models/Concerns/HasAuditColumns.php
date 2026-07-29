<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fills created_by / updated_by automatically.
 *
 * Doing this in the service layer instead means every create() and update()
 * has to remember, and the one that forgets produces a row nobody can trace.
 * Model events cannot be skipped by accident.
 *
 * Guarded against a null user so seeders and console commands still work.
 */
trait HasAuditColumns
{
    public static function bootHasAuditColumns(): void
    {
        static::creating(function ($model) {
            $id = auth()->id();

            $model->created_by ??= $id;
            $model->updated_by ??= $id;
        });

        static::updating(function ($model) {
            if ($id = auth()->id()) {
                $model->updated_by = $id;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
