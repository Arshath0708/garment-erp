<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One free-text BOM component on Product Master.
 * Qty is per finished piece (Task 12/13 approval).
 */
class ProductBomItem extends Model
{
    protected $fillable = [
        'product_id',
        'sort_order',
        'component_name',
        'qty',
        'unit',
        'is_custom',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'qty'        => 'decimal:4',
            'is_custom'  => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
