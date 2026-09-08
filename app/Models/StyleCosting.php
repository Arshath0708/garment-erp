<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StyleCosting extends Model
{
    use HasAuditColumns;

    public const STATUSES = [
        'draft'    => 'Draft',
        'approved' => 'Approved',
    ];

    public const STATUS_COLORS = [
        'draft'    => 'secondary',
        'approved' => 'success',
    ];

    protected $fillable = [
        'costing_date',
        'garment_style_id',
        'buyer_id',
        'cm_cost',
        'other_cost',
        'material_cost',
        'total_cost_per_pc',
        'status',
        'approved_at',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'costing_date'      => 'date',
            'cm_cost'           => 'decimal:4',
            'other_cost'        => 'decimal:4',
            'material_cost'     => 'decimal:4',
            'total_cost_per_pc' => 'decimal:4',
            'approved_at'       => 'datetime',
        ];
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StyleCostingLine::class)->orderBy('sort_order');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
