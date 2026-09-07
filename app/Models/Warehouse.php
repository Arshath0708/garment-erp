<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    public const KINDS = [
        'fabric'   => 'Fabric / Trims',
        'finished' => 'Finished Goods',
        'other'    => 'Other',
    ];

    protected $fillable = [
        'code',
        'name',
        'kind',
        'is_active',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockLots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public static function defaultFabric(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('code', 'MAIN')
            ->first()
            ?? static::query()->where('is_active', true)->where('kind', 'fabric')->orderBy('id')->first();
    }
}
