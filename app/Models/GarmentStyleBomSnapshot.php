<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarmentStyleBomSnapshot extends Model
{
    protected $fillable = [
        'garment_style_id',
        'version',
        'materials',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'materials'    => 'array',
            'version'      => 'integer',
            'approved_at'  => 'datetime',
        ];
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
