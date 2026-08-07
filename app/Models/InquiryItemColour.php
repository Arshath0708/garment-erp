<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One colour row under an inquiry item. Always at least one per item, even
 * when the item's format has allow_multiple_colours off — `colour` is simply
 * blank then, and the single row carries the size breakdown on its own.
 */
class InquiryItemColour extends Model
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
        return $this->belongsTo(InquiryItem::class, 'inquiry_item_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(InquiryItemSize::class, 'inquiry_item_colour_id')->orderBy('sort_order');
    }
}
