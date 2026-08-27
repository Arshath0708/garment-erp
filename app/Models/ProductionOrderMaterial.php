<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $fillable = [
        'production_order_id',
        'product_id',
        'required_qty',
        'use_stock_qty',
        'buy_qty',
    ];

    protected function casts(): array
    {
        return [
            'required_qty'  => 'decimal:3',
            'use_stock_qty' => 'decimal:3',
            'buy_qty'       => 'decimal:3',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
