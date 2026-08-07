<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItemSize extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'size',
        'qty',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function colour(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItemColour::class, 'purchase_order_item_colour_id');
    }
}
