<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GarmentStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'style_number',
        'buyer_style_no',
        'factory_style_no',
        'name',
        'buyer_id',
        'category_id',
        'season',
        'color',
        'design',
        'fabric',
        'sizes',
        'target_qty',
        'logo_path',
        'image_path',
        'tech_specs',
        'status',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(GarmentStyleMaterial::class)->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GarmentStyleComment::class)->orderByDesc('id');
    }
}
