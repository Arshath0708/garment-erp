<?php

namespace App\Services\Masters;

use App\Models\Agent;
use Illuminate\Support\Facades\DB;

class AgentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Agent
    {
        return DB::transaction(function () use ($data) {
            [$data, $categories, $commissions] = $this->extractChildren($data);

            $agent = Agent::create($data);
            $agent->categories()->sync($categories);
            $this->syncCommissions($agent, $commissions);

            return $agent;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Agent $agent, array $data): Agent
    {
        return DB::transaction(function () use ($agent, $data) {
            [$data, $categories, $commissions] = $this->extractChildren($data);

            $agent->update($data);
            $agent->categories()->sync($categories);
            $this->syncCommissions($agent, $commissions);

            return $agent->refresh();
        });
    }

    /**
     * Splits the validated payload into the agent's own columns and its two
     * child collections, so create() and update() do not each have to remember
     * which keys are not columns.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, int>, 2: array<int, array<string, mixed>>}
     */
    private function extractChildren(array $data): array
    {
        $categories  = $data['categories'] ?? [];
        $commissions = $data['commissions'] ?? [];

        unset($data['categories'], $data['commissions']);

        return [$data, $categories, $commissions];
    }

    /**
     * Replace the agent's commission entries with the posted set.
     *
     * Delete-and-reinsert rather than a diff. The rows carry no identity a user
     * would recognise — they are a short ordered list edited as a whole, the
     * way the carton-marking lines on the Buyer master are — and nothing
     * references an `agent_commissions.id`, so preserving one buys nothing.
     * A diff here would be more code and one more thing to get wrong.
     *
     * `sort_order` comes from the array position, because the costing panel
     * treats the first entry as the one that applies. Blank rows left behind by
     * the repeater's "remove" button are skipped, not saved as zeroes.
     *
     * @param  array<int, array<string, mixed>>  $commissions
     */
    private function syncCommissions(Agent $agent, array $commissions): void
    {
        $agent->commissions()->delete();

        $rows = [];
        $order = 0;

        foreach ($commissions as $commission) {
            if (blank($commission['amount'] ?? null)) {
                continue;
            }

            $rows[] = [
                'commission_type' => $commission['commission_type'],
                'amount'          => $commission['amount'],
                'currency_id'     => $commission['currency_id'] ?? null,
                'sort_order'      => $order++,
            ];
        }

        if ($rows !== []) {
            $agent->commissions()->createMany($rows);
        }
    }

    /**
     * Whether the agent can be deleted safely.
     *
     * A buyer or supplier pointing at a deleted agent would lose the commission
     * trail on every order already placed through them, and the FK is
     * nullOnDelete — so the reference would vanish silently rather than fail.
     * Checked here so the answer is a sentence rather than a broken report
     * three months later.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Agent $agent): array
    {
        $buyers    = $agent->buyers()->count();
        $suppliers = $agent->suppliers()->count();

        if ($buyers === 0 && $suppliers === 0) {
            return ['allowed' => true, 'reason' => null];
        }

        $used = [];

        if ($buyers > 0) {
            $used[] = $buyers.' '.str('buyer')->plural($buyers);
        }

        if ($suppliers > 0) {
            $used[] = $suppliers.' '.str('supplier')->plural($suppliers);
        }

        return [
            'allowed' => false,
            'reason'  => "{$agent->name} is linked to ".implode(' and ', $used).
                '. Reassign them before deleting this agent, or mark it inactive instead.',
        ];
    }
}
