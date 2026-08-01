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
     * "Who Pays This Commission?" on the client's prototype.
     *
     * Not a cosmetic label — it decides which ledger the commission lands in.
     * `us` is our payable and produces a debit note; `supplier` is a deduction
     * from the supplier's bill; `buyer` is a deduction from the export invoice.
     *
     * @var array<string, string>
     */
    public const COMMISSION_PAYERS = [
        'us'       => 'We pay',
        'supplier' => 'Supplier pays',
        'buyer'    => 'Buyer pays',
    ];

    /**
     * When the commission falls due, and therefore when a debit note can be
     * raised against it. `custom` is described in `payment_term_custom`.
     *
     * @var array<string, string>
     */
    public const PAYMENT_TERMS = [
        'after_buyer'    => 'After buyer payment is received by us',
        'after_supplier' => 'After supplier payment is made',
        'on_shipment'    => 'On shipment / BL date',
        'monthly'        => 'Monthly settlement',
        'custom'         => 'Custom',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agent_type',
        'name',
        'display_code',
        'phone',
        'city',
        'address',
        'gst_number',
        'pan_number',
        'bank_name',
        'account_number',
        'ifsc_code',
        'swift_code',
        'calculation_basis_id',
        'commission_paid_by',
        'payment_term',
        'payment_term_custom',
        'status',
        'remarks',
    ];

    /*
    |--------------------------------------------------------------------------
    | Side-dependent fields
    |--------------------------------------------------------------------------
    | The prototype shows and hides whole blocks by agent type. The lists live
    | here rather than in the form request or the Blade file, because all three
    | need the same answer and a fourth caller (the show page) would otherwise
    | invent its own.
    */

    /** Tax details are collected for domestic agents only. */
    public function collectsTaxDetails(): bool
    {
        return $this->agent_type !== 'buyer';
    }

    /** IFSC for a domestic transfer, SWIFT for an international one. */
    public function bankCodeField(): string
    {
        return $this->agent_type === 'buyer' ? 'swift_code' : 'ifsc_code';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
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

    /**
     * Ordered here so no caller has to remember to sort. The costing panel
     * treats the first entry as the one that applies, so the order is data and
     * not presentation.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AgentCommission::class)->orderBy('sort_order');
    }

    public function buyers(): HasMany
    {
        return $this->hasMany(Buyer::class);
    }

    /** Supplier sheet col X — supplier-side and jobber-side agents. */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
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
        return ['display_code', 'name', 'city', 'phone', 'remarks'];
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
