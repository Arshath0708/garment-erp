<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FobValue extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'remarks',
    ];

    public function searchable(): array
    {
        return ['name', 'remarks'];
    }

    public function sortable(): array
    {
        return ['id', 'name', 'status', 'created_at'];
    }
}
