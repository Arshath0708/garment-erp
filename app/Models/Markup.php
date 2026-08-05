<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Markup Master — the margin taken on one supplier's goods for one buyer.
 *
 * The three lines the prototype prints under the percentage fields are the
 * whole of the arithmetic, and they are implemented here rather than in Blade
 * so the preview on the form and whatever an OC or PO computes later cannot
 * drift apart:
 *
 *     clientPrice(cp) = cp + markup%      "Added on top of cost price"
 *     ourCost(cp)     = cp - discount%    from the supplier master
 *     profit(cp)      = clientPrice - ourCost
 *
 * The discount is deliberately not a column — it lives on the supplier, and
 * the prototype's caption says so: "Auto-filled from Supplier Master · edit
 * there to change".
 */
class Markup extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * `record_date` is absent: the prototype captions it "Auto-generated on
     * record creation" and renders it read-only, so accepting it from the
     * request would let a crafted POST backdate a rule.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'buyer_id',
        'markup_percent',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'record_date'    => 'date',
            'markup_percent' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | The arithmetic
    |--------------------------------------------------------------------------
    */

    /**
     * The supplier's negotiated discount, as a number rather than a nullable
     * decimal string. Absent means no discount, not an unknown one.
     */
    public function discountPercent(): float
    {
        return (float) ($this->supplier?->discount_percent ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Agent commission — read straight off each party, for visibility
    |--------------------------------------------------------------------------
    | Not a column on this table, same reasoning as the discount above: each
    | party's own agent_commission_type/value on the Agent Master screen is
    | "this party's negotiated rate with this agent" — the same agent can
    | carry a different rate for another buyer or supplier — so that is the
    | one true source, not a figure copied onto the markup rule.
    */

    /** The supplier's own linked agent, or null if it has none. */
    public function supplierAgent(): ?Agent
    {
        return $this->supplier?->agent;
    }

    /** The supplier's negotiated commission with that agent — "2.5%" or "1.5 INR". */
    public function supplierAgentCommissionLabel(): ?string
    {
        return $this->supplier?->agent_commission_label;
    }

    /** The buyer's own linked agent, or null if it has none. */
    public function buyerAgent(): ?Agent
    {
        return $this->buyer?->agent;
    }

    /** The buyer's negotiated commission with that agent — "2.5%" or "1.5000 USD". */
    public function buyerAgentCommissionLabel(): ?string
    {
        return $this->buyer?->agent_commission_label;
    }

    /** CP + Markup% */
    public function clientPrice(float $costPrice): float
    {
        return round($costPrice * (1 + ((float) $this->markup_percent / 100)), 2);
    }

    /** CP − Discount% */
    public function ourCost(float $costPrice): float
    {
        return round($costPrice * (1 - ($this->discountPercent() / 100)), 2);
    }

    /**
     * Client Price − Our Cost.
     *
     * Computed from the two rounded halves rather than from an unrounded
     * expression, so the number shown equals the two numbers shown beside it
     * minus one another. A profit that does not reconcile with the figures
     * printed next to it is the kind of thing that gets an ERP distrusted.
     */
    public function profit(float $costPrice): float
    {
        return round($this->clientPrice($costPrice) - $this->ourCost($costPrice), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    /**
     * Search reaches through to the two parties, because "who is this rule
     * for" is the only way anyone will look for one — the row itself has no
     * name of its own.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->whereHas('supplier', fn (Builder $s) => $s
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('display_code', 'like', "%{$term}%"))
                ->orWhereHas('buyer', fn (Builder $b) => $b
                    ->where('company_name', 'like', "%{$term}%")
                    ->orWhere('display_code', 'like', "%{$term}%"))
                ->orWhere('remarks', 'like', "%{$term}%");
        });
    }

    /**
     * @return array<int, string>
     */
    public function sortable(): array
    {
        return ['id', 'markup_percent', 'record_date', 'status', 'created_at'];
    }
}
