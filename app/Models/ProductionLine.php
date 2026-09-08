<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLine extends Model
{
    protected $fillable = [
        'name',
        'target_pcs_per_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_pcs_per_day' => 'integer',
            'is_active'          => 'boolean',
        ];
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionLineOutput::class);
    }

    public function todaysPcs(?string $date = null): int
    {
        $day = $date ?: now()->toDateString();

        return (int) $this->outputs()->whereDate('output_date', $day)->sum('pcs');
    }

    public function efficiencyPct(?string $date = null): float
    {
        if ($this->target_pcs_per_day <= 0) {
            return 0;
        }

        return round(100 * $this->todaysPcs($date) / $this->target_pcs_per_day, 1);
    }
}
