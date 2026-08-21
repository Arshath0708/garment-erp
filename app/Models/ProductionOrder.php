<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrder extends Model
{
    use HasFactory;

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
        'stitching_qty',
        'finishing_qty',
        'qc_passed_qty',
        'qc_rejected_qty',
        'packing_qty',
        'dispatch_qty',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
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
}
