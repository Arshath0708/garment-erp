<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agent_type',
        'name',
        'display_code',
        'calculation_basis_id',
        'commission_rate', // Reserved for future use
        'status',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'agent_category');
    }

    public function commissionBasis(): BelongsTo
    {
        return $this->belongsTo(CalculationBasis::class, 'calculation_basis_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['display_code', 'name', 'remarks'];
    }

    /**
     * @return array<int, string>
     */
    public function sortable(): array
    {
        return ['id', 'display_code', 'name', 'status', 'created_at'];
    }
}
