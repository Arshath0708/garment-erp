<?php

namespace App\Services\Masters;

use App\Models\Markup;
use Illuminate\Support\Carbon;

class MarkupService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Markup
    {
        $markup = new Markup($data);

        // "Auto-generated on record creation". Assigned as a property rather
        // than mass-assigned, because record_date is not fillable — that is
        // what stops a crafted POST from backdating a rule.
        $markup->record_date = Carbon::today();
        $markup->save();

        return $markup;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Markup $markup, array $data): Markup
    {
        // The record date is when the rule was written, not when it was last
        // touched — updated_at already carries that.
        unset($data['record_date']);

        $markup->update($data);

        return $markup->refresh();
    }

    /**
     * Nothing points at a markup yet — order confirmations and purchase orders
     * arrive in later phases. The check exists now so the guard is in one place
     * when they do, and so the controller's shape matches the other masters.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Markup $markup): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    /**
     * The three lines the prototype prints, worked out against a sample cost
     * price so the form can show what the rule will actually do.
     *
     * @return array{cost: float, client_price: float, our_cost: float, profit: float, discount: float}
     */
    public function preview(Markup $markup, float $costPrice = 100.0): array
    {
        return [
            'cost'         => $costPrice,
            'discount'     => $markup->discountPercent(),
            'client_price' => $markup->clientPrice($costPrice),
            'our_cost'     => $markup->ourCost($costPrice),
            'profit'       => $markup->profit($costPrice),
        ];
    }
}
