<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExportDocumentItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_confirmation_item_id',
        'sort_order',
        'design_no',
        'description',
        'product_id',
        'unit',
        'price',
        'qty',
        'amount',
        'remarks',
        'custom_values',
    ];

    protected function casts(): array
    {
        return [
            'sort_order'    => 'integer',
            'price'         => 'decimal:2',
            'qty'           => 'integer',
            'amount'        => 'decimal:2',
            'custom_values' => 'array',
        ];
    }

    public function exportDocument(): BelongsTo
    {
        return $this->belongsTo(ExportDocument::class);
    }

    /** The OC line this was raised from — read-only reference. */
    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmationItem::class, 'order_confirmation_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function colours(): HasMany
    {
        return $this->hasMany(ExportDocumentItemColour::class)->orderBy('sort_order');
    }
}
