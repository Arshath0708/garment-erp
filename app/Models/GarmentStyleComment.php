<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarmentStyleComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'garment_style_id',
        'user_id',
        'user_name',
        'comment',
    ];

    public function garmentStyle(): BelongsTo
    {
        return $this->belongsTo(GarmentStyle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
