<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One line of an OC's item table. Rewritten wholesale on every OC save, same
 * as InquiryItem — except its purchase_order_id/raised_at, which
 * OrderConfirmationService::raisePurchaseOrders() sets directly and the
 * ordinary item resync must never touch, or a raised item would quietly
 * become raisable again the next time the OC is saved.
 */
class OrderConfirmationItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sort_order',
        'design_no',
        'description',
        'product_id',
        'supplier_id',
        'unit',
        'fob_value_id',
        'price',
        'cost_price',
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
            'cost_price'    => 'decimal:2',
            'qty'           => 'integer',
            'amount'        => 'decimal:2',
            'custom_values' => 'array',
            'raised_at'     => 'datetime',
            'shipped_at'    => 'datetime',
        ];
    }

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function fobValue(): BelongsTo
    {
        return $this->belongsTo(FobValue::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function exportDocument(): BelongsTo
    {
        return $this->belongsTo(ExportDocument::class);
    }

    public function colours(): HasMany
    {
        return $this->hasMany(OrderConfirmationItemColour::class)->orderBy('sort_order');
    }

    public function isRaised(): bool
    {
        return $this->purchase_order_id !== null;
    }

    public function isShipped(): bool
    {
        return $this->export_document_id !== null;
    }
}
