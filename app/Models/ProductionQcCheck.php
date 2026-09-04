<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionQcCheck extends Model
{
    public const RESULTS = [
        'pass' => 'Pass',
        'fail' => 'Fail',
    ];

    public const CAPA_STATUSES = [
        'open'   => 'Open',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'production_order_id',
        'stage',
        'checked_qty',
        'passed_qty',
        'failed_qty',
        'result',
        'defect_code_id',
        'notes',
        'capa_plan',
        'capa_due_date',
        'capa_status',
        'capa_closed_at',
        'capa_closed_by',
        'held_work_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_qty'     => 'integer',
            'passed_qty'      => 'integer',
            'failed_qty'      => 'integer',
            'held_work_order' => 'boolean',
            'capa_due_date'   => 'date',
            'capa_closed_at'  => 'datetime',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function defectCode(): BelongsTo
    {
        return $this->belongsTo(DefectCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function capaCloser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capa_closed_by');
    }

    public function isFail(): bool
    {
        return $this->result === 'fail';
    }

    public function hasOpenCapa(): bool
    {
        return $this->capa_status === 'open';
    }

    public function stageLabel(): string
    {
        return ProductionOrder::STAGE_KEYS[$this->stage]['label'] ?? $this->stage;
    }
}
