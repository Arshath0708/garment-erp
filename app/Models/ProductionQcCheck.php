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

    protected $fillable = [
        'production_order_id',
        'stage',
        'checked_qty',
        'passed_qty',
        'failed_qty',
        'result',
        'notes',
        'held_work_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_qty'      => 'integer',
            'passed_qty'       => 'integer',
            'failed_qty'       => 'integer',
            'held_work_order'  => 'boolean',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFail(): bool
    {
        return $this->result === 'fail';
    }

    public function stageLabel(): string
    {
        return ProductionOrder::STAGE_KEYS[$this->stage]['label'] ?? $this->stage;
    }
}
