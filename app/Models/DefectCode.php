<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefectCode extends Model
{
    public const CATEGORIES = [
        'cutting'    => 'Cutting',
        'stitching'  => 'Stitching',
        'fabric'     => 'Fabric',
        'finishing'  => 'Finishing',
        'other'      => 'Other',
    ];

    protected $fillable = [
        'code',
        'name',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function qcChecks(): HasMany
    {
        return $this->hasMany(ProductionQcCheck::class);
    }

    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
