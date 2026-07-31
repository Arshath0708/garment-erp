<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * Agent sheet col A: "drop down menu with a supplier side, buyer side and
     * jobber side". One side per agent — unlike col D (category), the sheet
     * does not say "allow multiple choice" here. An agent who works two sides
     * gets a record per side, each with its own display code.
     *
     * The Buyer form's col O filter and the Supplier form's col X filter both
     * read this column.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'supplier' => 'Supplier',
        'buyer'    => 'Buyer',
        'jobber'   => 'Jobber',
    ];

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

    public function buyers(): HasMany
    {
        return $this->hasMany(Buyer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Buyer sheet col O and Supplier col X: "only those which are a part of
     * buyer side / supplier side selected in agent master should show here".
     *
     * One scope, three call sites — the Buyer, Supplier and Jobber forms.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('agent_type', $type);
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

    /** "David (AG01)" — the code disambiguates two agents with the same name. */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => "{$this->name} ({$this->display_code})");
    }
}
