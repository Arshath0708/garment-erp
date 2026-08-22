<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrder extends Model
{
    use HasFactory;

    /**
     * Size columns used on the floor grid and job-work delivery challan.
     *
     * @var list<string>
     */
    public const SIZES = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];

    /**
     * Stage key => label + the integer total column kept in sync with the row sum.
     *
     * @var array<string, array{label: string, qty_column: string}>
     */
    public const STAGE_KEYS = [
        'cutting'    => ['label' => 'Cutting', 'qty_column' => 'cutting_qty'],
        'printing'   => ['label' => 'Printing / Embroidery', 'qty_column' => 'printing_qty'],
        'stitching'  => ['label' => 'Stitching', 'qty_column' => 'stitching_qty'],
        'finishing'  => ['label' => 'Finishing', 'qty_column' => 'finishing_qty'],
        'qc_passed'  => ['label' => 'QC Pass', 'qty_column' => 'qc_passed_qty'],
        'packing'    => ['label' => 'Packing', 'qty_column' => 'packing_qty'],
        'dispatch'   => ['label' => 'Dispatch', 'qty_column' => 'dispatch_qty'],
    ];

    /**
     * @var array<string, string>
     */
    public const JOB_WORK_TYPES = [
        'in_house'    => 'In-house (own floor)',
        'printing'    => 'Job work — Printing only',
        'embroidery'  => 'Job work — Embroidery only',
        'stitching'   => 'Job work — Stitching',
        'finishing'   => 'Job work — Finishing',
    ];

    protected $fillable = [
        'order_number',
        'order_confirmation_id',
        'garment_style_id',
        'buyer_id',
        'total_qty',
        'target_date',
        'current_stage',
        'status',
        'cutting_qty',
        'printing_qty',
        'stitching_qty',
        'finishing_qty',
        'qc_passed_qty',
        'qc_rejected_qty',
        'packing_qty',
        'dispatch_qty',
        'size_breakdown',
        'notes',
        'job_work_type',
        'jobber_id',
        'place_of_supply',
        'vehicle_no',
        'driver_name',
        'challan_no',
    ];

    protected $casts = [
        'target_date'     => 'date',
        'size_breakdown'  => 'array',
    ];

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function jobber(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'jobber_id');
    }

    /**
     * Qty for one stage + size. Missing keys read as 0.
     */
    public function sizeQty(string $stage, string $size): int
    {
        return (int) data_get($this->size_breakdown, "{$stage}.{$size}", 0);
    }

    public function stageSizeTotal(string $stage): int
    {
        $row = $this->size_breakdown[$stage] ?? [];

        return (int) collect($row)->sum();
    }

    /**
     * Which size row to print on a job-work challan (what we are sending out).
     */
    public function challanStageKey(): string
    {
        return match ($this->job_work_type) {
            'printing', 'embroidery' => 'cutting',
            'stitching'              => 'printing',
            'finishing'              => 'stitching',
            default                  => 'cutting',
        };
    }

    public function jobWorkTypeLabel(): string
    {
        return self::JOB_WORK_TYPES[$this->job_work_type] ?? $this->job_work_type;
    }

    /**
     * @return array{breakdown: array<string, array<string, int>>, totals: array<string, int>}
     */
    public static function parseSizePayload(?array $sizes): array
    {
        $breakdown = [];
        $totals = [];

        foreach (self::STAGE_KEYS as $key => $meta) {
            $row = [];
            $sum = 0;
            foreach (self::SIZES as $size) {
                $qty = max(0, (int) ($sizes[$key][$size] ?? 0));
                $row[$size] = $qty;
                $sum += $qty;
            }
            $breakdown[$key] = $row;
            $totals[$meta['qty_column']] = $sum;
        }

        return ['breakdown' => $breakdown, 'totals' => $totals];
    }
}
