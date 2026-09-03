<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasAuditColumns;

    public const STATUSES = [
        'draft'    => 'Draft',
        'released' => 'Released',
        'hold'     => 'Hold',
    ];

    public const STATUS_COLORS = [
        'draft'    => 'secondary',
        'released' => 'success',
        'hold'     => 'warning',
    ];

    /**
     * T&A steps counted back from target / delivery date.
     *
     * @var array<string, array{label: string, days_before: int}>
     */
    public const TNA_STEPS = [
        'fabric_inward' => ['label' => 'Fabric inward', 'days_before' => 25],
        'cutting'       => ['label' => 'Cutting', 'days_before' => 18],
        'printing'      => ['label' => 'Printing / Embroidery', 'days_before' => 14],
        'stitching'     => ['label' => 'Stitching', 'days_before' => 10],
        'finishing'     => ['label' => 'Finishing', 'days_before' => 6],
        'qc'            => ['label' => 'QC Pass', 'days_before' => 4],
        'packing'       => ['label' => 'Packing', 'days_before' => 2],
        'dispatch'      => ['label' => 'Dispatch', 'days_before' => 0],
    ];

    /**
     * Production size-grid keys → T&A step keys.
     *
     * @var array<string, string>
     */
    public const PRODUCTION_TO_TNA = [
        'cutting'   => 'cutting',
        'printing'  => 'printing',
        'stitching' => 'stitching',
        'finishing' => 'finishing',
        'qc_passed' => 'qc',
        'packing'   => 'packing',
        'dispatch'  => 'dispatch',
    ];

    protected $fillable = [
        'wo_date',
        'garment_style_id',
        'order_confirmation_id',
        'buyer_id',
        'total_qty',
        'target_date',
        'status',
        'released_at',
        'released_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'wo_date'     => 'date',
            'target_date' => 'date',
            'released_at' => 'datetime',
        ];
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TimeAndActionStep::class)->orderBy('sort_order');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('status', 'released');
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function lateStepsCount(): int
    {
        return $this->steps->filter(fn (TimeAndActionStep $step) => $step->isLate())->count();
    }

    public function step(string $key): ?TimeAndActionStep
    {
        return $this->steps->firstWhere('step_key', $key);
    }
}
