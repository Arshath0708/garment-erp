<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobWorkVoucher extends Model
{
    use HasAuditColumns;

    public const TYPES = [
        'issue'   => 'Issue (sent out)',
        'receive' => 'Receive (came back)',
    ];

    public const TYPE_COLORS = [
        'issue'   => 'primary',
        'receive' => 'success',
    ];

    protected $fillable = [
        'voucher_date',
        'type',
        'jobber_id',
        'production_order_id',
        'garment_style_id',
        'process',
        'vehicle_no',
        'total_qty',
        'damaged_qty',
        'rate_per_pc',
        'charge_amount',
        'size_qty',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'size_qty'     => 'array',
            'total_qty'    => 'integer',
            'damaged_qty'  => 'integer',
            'rate_per_pc'  => 'decimal:4',
            'charge_amount' => 'decimal:2',
        ];
    }

    public function jobber(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'jobber_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class, 'source_id')->where('source_type', DebitNote::SOURCE_JOB_WORK);
    }

    public function isIssue(): bool
    {
        return $this->type === 'issue';
    }

    public function isReceive(): bool
    {
        return $this->type === 'receive';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function typeColor(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'secondary';
    }

    public function goodQty(): int
    {
        return max(0, $this->total_qty - $this->damaged_qty);
    }

    public function sizeQty(string $size): int
    {
        return (int) ($this->size_qty[$size] ?? 0);
    }
}
