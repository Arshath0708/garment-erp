<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TimeAndActionStep extends Model
{
    protected $fillable = [
        'work_order_id',
        'step_key',
        'label',
        'sort_order',
        'planned_date',
        'actual_date',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'actual_date'  => 'date',
            'sort_order'   => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function isLate(?Carbon $today = null): bool
    {
        if ($this->actual_date) {
            return $this->actual_date->gt($this->planned_date);
        }

        $today ??= now()->startOfDay();

        return $this->planned_date->lt($today);
    }

    public function daysLate(?Carbon $today = null): int
    {
        if (! $this->isLate($today)) {
            return 0;
        }

        $end = $this->actual_date ?? ($today ?? now()->startOfDay());
        $planned = $this->planned_date->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        return (int) max(0, $planned->diffInDays($endDay));
    }

    public function statusLabel(): string
    {
        if ($this->actual_date) {
            return $this->actual_date->gt($this->planned_date) ? 'Late (done)' : 'Done';
        }

        return $this->isLate() ? 'Late' : 'On time';
    }

    public function statusColor(): string
    {
        if ($this->actual_date) {
            return $this->actual_date->gt($this->planned_date) ? 'warning' : 'success';
        }

        return $this->isLate() ? 'danger' : 'secondary';
    }
}
