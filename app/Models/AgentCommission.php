<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One commission entry on an agent — the "+ Add commission entry" rows on the
 * client's prototype.
 *
 * Kept as rows rather than a pair of columns on `agents` because the prototype
 * allows several, and because a percentage and a fixed amount per piece are not
 * interchangeable: the settlement screen has to know which one it is holding
 * before it can multiply anything. See the 000100 expand migration.
 */
class AgentCommission extends Model
{
    /**
     * `percent` is a percentage of the agent's commission basis — Net Value for
     * a supplier-side agent, FOB Value for a buyer-side one, per
     * `agents.calculation_basis_id`. `fixed` is an amount per piece and ignores
     * the basis entirely.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'percent' => '% of value',
        'fixed'   => 'Fixed / piece',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agent_id',
        'commission_type',
        'amount',
        'currency_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Null for supplier-side entries — those are always INR and the prototype
     * hides the picker for them.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * "2.5%" or "INR 12.00 / piece". One place builds the string, so the list,
     * the show page and any future debit note read the same wording.
     */
    protected function label(): Attribute
    {
        return Attribute::get(function () {
            $amount = rtrim(rtrim(number_format((float) $this->amount, 4, '.', ''), '0'), '.');

            if ($this->commission_type === 'percent') {
                return "{$amount}%";
            }

            $code = $this->currency?->iso_code ?? 'INR';

            return "{$code} {$amount} / piece";
        });
    }
}
