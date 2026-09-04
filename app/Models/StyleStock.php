<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StyleStock extends Model
{
    protected $fillable = [
        'garment_style_id',
        'qty_on_hand',
    ];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'integer',
        ];
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }
}
