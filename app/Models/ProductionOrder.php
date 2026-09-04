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
        'work_order_id',
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

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function jobber(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'jobber_id');
    }

    public function materials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function qcChecks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductionQcCheck::class)->latest('id');
    }

    public function jobWorkVouchers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JobWorkVoucher::class);
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
        $sum = 0;
        foreach (self::SIZES as $size) {
            $sum += (int) ($row[$size] ?? 0);
        }

        return $sum;
    }

    public function stageDamage(string $stage): int
    {
        return max(0, (int) data_get($this->size_breakdown, "{$stage}.damage", 0));
    }

    /** Pieces that can move to the next stage (qty minus damage). */
    public function stageGoodQty(string $stage): int
    {
        return max(0, $this->stageSizeTotal($stage) - $this->stageDamage($stage));
    }

    /**
     * Stages that already have size qty or damage entered.
     *
     * @return list<array{key: string, label: string, sizes: array<string, int>, total: int, damage: int}>
     */
    public function filledStageRows(): array
    {
        $rows = [];
        foreach (self::STAGE_KEYS as $key => $meta) {
            $total = $this->stageSizeTotal($key);
            $damage = $this->stageDamage($key);
            if ($total === 0 && $damage === 0) {
                continue;
            }

            $sizes = [];
            foreach (self::SIZES as $size) {
                $sizes[$size] = $this->sizeQty($key, $size);
            }

            $rows[] = [
                'key'    => $key,
                'label'  => $meta['label'],
                'sizes'  => $sizes,
                'total'  => $total,
                'damage' => $damage,
            ];
        }

        return $rows;
    }

    public static function stageKeyFromLabel(?string $label): string
    {
        return match ($label) {
            'Printing', 'Printing / Embroidery' => 'printing',
            'Stitching'                         => 'stitching',
            'Finishing'                         => 'finishing',
            'Quality Check'                     => 'qc_passed',
            'Packing'                           => 'packing',
            'Dispatch'                          => 'dispatch',
            default                             => 'cutting',
        };
    }

    /**
     * Size-breakdown key for the order’s current active stage label.
     */
    public function currentStageKey(): string
    {
        return self::stageKeyFromLabel($this->current_stage);
    }

    /**
     * Which size row to print on a job-work challan (what we are sending out).
     * In-house / unspecified: the current active stage. Job-work still sends
     * the previous stage’s goods (cut pieces to the printer, etc.).
     */
    public function challanStageKey(): string
    {
        if (! $this->job_work_type || $this->job_work_type === 'in_house') {
            return $this->currentStageKey();
        }

        return match ($this->job_work_type) {
            'printing', 'embroidery' => 'cutting',
            'stitching'              => 'printing',
            'finishing'              => 'stitching',
            default                  => $this->currentStageKey(),
        };
    }

    public function jobWorkTypeLabel(): string
    {
        return self::JOB_WORK_TYPES[$this->job_work_type] ?? $this->job_work_type;
    }

    public function pendingCuttingQty(): int
    {
        $nextWorked = max($this->printing_qty, $this->stitching_qty);

        return max(0, $this->cutting_qty - $nextWorked);
    }

    public function pendingPrintingQty(): int
    {
        return max(0, $this->printing_qty - $this->stitching_qty);
    }

    public function pendingStitchingQty(): int
    {
        return max(0, $this->stitching_qty - $this->finishing_qty);
    }

    public function pendingFinishingQty(): int
    {
        return max(0, $this->finishing_qty - ($this->qc_passed_qty + $this->qc_rejected_qty));
    }

    public function pendingQcQty(): int
    {
        return max(0, $this->qc_passed_qty - $this->packing_qty);
    }

    public function pendingPackingQty(): int
    {
        return max(0, $this->packing_qty - $this->dispatch_qty);
    }

    public function stageWipBalance(string $stage): int
    {
        return match ($stage) {
            'cutting'    => $this->pendingCuttingQty(),
            'printing'   => $this->pendingPrintingQty(),
            'stitching'  => $this->pendingStitchingQty(),
            'finishing'  => $this->pendingFinishingQty(),
            'qc', 'qc_passed' => $this->pendingQcQty(),
            'packing'    => $this->pendingPackingQty(),
            default      => 0,
        };
    }

    /**
     * @return array{breakdown: array<string, array<string, int>>, totals: array<string, int>}
     */
    public static function parseSizePayload(?array $sizes, ?array $damage = null): array
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
            $row['damage'] = max(0, (int) ($damage[$key] ?? $sizes[$key]['damage'] ?? 0));
            $breakdown[$key] = $row;
            $totals[$meta['qty_column']] = $sum;
        }

        return ['breakdown' => $breakdown, 'totals' => $totals];
    }

    /**
     * Next stage cannot exceed previous stage’s good pcs (qty − damage).
     * Each size cannot exceed the same size on the previous filled stage.
     * Damage cannot exceed that stage’s own qty.
     *
     * @param  array<string, array<string, int>>  $breakdown
     * @return array<string, string>
     */
    public static function stageFlowErrors(array $breakdown, int $orderQty): array
    {
        $errors = [];
        $prevGood = max(0, $orderQty);
        $prevLabel = 'order qty';
        $prevSizes = [];

        foreach (self::STAGE_KEYS as $key => $meta) {
            $total = 0;
            foreach (self::SIZES as $size) {
                $total += (int) ($breakdown[$key][$size] ?? 0);
            }
            $dmg = max(0, (int) ($breakdown[$key]['damage'] ?? 0));
            $label = $meta['label'];

            if ($total === 0 && $dmg === 0) {
                continue;
            }

            if ($dmg > $total) {
                $errors["damage.{$key}"] = "{$label} damage ({$dmg}) cannot exceed {$label} qty ({$total}).";
            }

            if ($total > $prevGood) {
                $errors["sizes.{$key}"] = "{$label} qty ({$total}) cannot exceed {$prevLabel} good pcs ({$prevGood}). Damaged pieces cannot move to the next stage.";
            }

            foreach (self::SIZES as $size) {
                $qty = (int) ($breakdown[$key][$size] ?? 0);
                $prevSizeQty = $prevSizes[$size] ?? null;
                if ($prevSizeQty !== null && $qty > $prevSizeQty) {
                    $errors["sizes.{$key}.{$size}"] = "{$label} {$size} ({$qty}) cannot exceed {$prevLabel} {$size} ({$prevSizeQty}). That size was not cut / processed earlier.";
                }
            }

            $prevGood = max(0, $total - $dmg);
            $prevLabel = $label;
            foreach (self::SIZES as $size) {
                $prevSizes[$size] = (int) ($breakdown[$key][$size] ?? 0);
            }
        }

        return $errors;
    }

    /**
     * Qty / damage for a later stage can only be entered after that stage is
     * selected as Current Active Stage. Existing later-stage numbers may stay.
     *
     * @param  array<string, array<string, int>>  $breakdown
     * @param  array<string, mixed>|null  $existingBreakdown
     * @return array<string, string>
     */
    public static function stageSelectionErrors(array $breakdown, ?string $currentStageLabel, ?array $existingBreakdown = null): array
    {
        $keys = array_keys(self::STAGE_KEYS);
        $activeIdx = array_search(self::stageKeyFromLabel($currentStageLabel), $keys, true);
        if ($activeIdx === false) {
            $activeIdx = 0;
        }

        $errors = [];
        foreach ($keys as $idx => $key) {
            if ($idx <= $activeIdx) {
                continue;
            }

            $changed = false;
            foreach (self::SIZES as $size) {
                $posted = (int) ($breakdown[$key][$size] ?? 0);
                $existing = (int) ($existingBreakdown[$key][$size] ?? 0);
                if ($posted !== $existing) {
                    $changed = true;
                    break;
                }
            }

            if (! $changed) {
                $postedDamage = (int) ($breakdown[$key]['damage'] ?? 0);
                $existingDamage = (int) ($existingBreakdown[$key]['damage'] ?? 0);
                $changed = $postedDamage !== $existingDamage;
            }

            if ($changed) {
                $label = self::STAGE_KEYS[$key]['label'];
                $errors["sizes.{$key}"] = "Select {$label} as Current Active Stage first, then enter {$label} quantities.";
            }
        }

        return $errors;
    }
}
