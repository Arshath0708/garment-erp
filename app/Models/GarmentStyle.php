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
        'bom_version',
        'bom_approved_at',
        'bom_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'target_qty'      => 'integer',
            'bom_version'     => 'integer',
            'bom_approved_at' => 'datetime',
        ];
    }

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

    public function costings(): HasMany
    {
        return $this->hasMany(StyleCosting::class)->latest('id');
    }

    public function latestApprovedCosting(): ?StyleCosting
    {
        if ($this->relationLoaded('costings')) {
            return $this->costings->firstWhere('status', 'approved');
        }

        return $this->costings()->where('status', 'approved')->first();
    }

    public function stock(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StyleStock::class);
    }

    public function bomApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bom_approved_by');
    }

    public function bomSnapshots(): HasMany
    {
        return $this->hasMany(GarmentStyleBomSnapshot::class)->orderByDesc('version');
    }

    public function isBomApproved(): bool
    {
        return $this->bom_approved_at !== null;
    }

    public static function resolveFromDesignNo(?string $designNo): ?self
    {
        if (! filled($designNo)) {
            return null;
        }

        return self::query()
            ->where(function ($q) use ($designNo) {
                $q->where('style_number', $designNo)
                    ->orWhere('buyer_style_no', $designNo)
                    ->orWhere('factory_style_no', $designNo)
                    ->orWhere('design', $designNo);
            })
            ->first();
    }
}
