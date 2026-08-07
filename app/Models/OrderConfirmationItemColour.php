<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderConfirmationItemColour extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'colour',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmationItem::class, 'order_confirmation_item_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(OrderConfirmationItemSize::class, 'order_confirmation_item_colour_id')->orderBy('sort_order');
    }
}
