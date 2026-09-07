<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLot extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'lot_no',
        'qty_on_hand',
        'received_at',
        'inward_entry_item_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'decimal:3',
            'received_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inwardEntryItem(): BelongsTo
    {
        return $this->belongsTo(InwardEntryItem::class);
    }
}
