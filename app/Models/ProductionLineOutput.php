<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLineOutput extends Model
{
    protected $fillable = [
        'production_line_id',
        'production_order_id',
        'output_date',
        'pcs',
        'notes',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'output_date' => 'date',
            'pcs' => 'integer',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
