<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StyleCostingLine extends Model
{
    protected $fillable = [
        'style_costing_id',
        'product_id',
        'description',
        'item_kind',
        'qty_per_pc',
        'unit',
        'rate',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty_per_pc' => 'decimal:4',
            'rate'       => 'decimal:4',
            'amount'     => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function costing(): BelongsTo
    {
        return $this->belongsTo(StyleCosting::class, 'style_costing_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function kindLabel(): string
    {
        return Product::KINDS[$this->item_kind] ?? ($this->item_kind ?: '—');
    }
}
