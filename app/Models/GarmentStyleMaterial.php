<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarmentStyleMaterial extends Model
{
    protected $fillable = [
        'garment_style_id',
        'product_id',
        'qty_per_pc',
        'unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty_per_pc' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
