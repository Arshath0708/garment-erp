<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

/**
 * The two agents named on the client's own Buyer Master example rows.
 *
 * The Agent Master screen is a later phase, so there is no way to create one
 * through the UI yet. Without these the Buyer form's agent dropdown (sheet
 * col O) is empty and the "only buyer-side agents" filter cannot be seen
 * working at all.
 *
 * Idempotent on `display_code`, like every other seeder here. Sides are synced
 * rather than inserted so re-running does not duplicate them.
 */
class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            // display_code, name, sides
            ['AG01', 'David', ['buyer']],
            ['AG02', 'James', ['buyer']],

            // Supplier-side only. Present specifically so the Buyer form can be
            // checked to NOT offer it — the filter is a requirement, not a
            // convenience, and an all-buyer-side list would never show a bug.
            ['AG03', 'Ravi',  ['supplier', 'jobber']],
        ];

        foreach ($agents as [$code, $name, $sides]) {
            $agent = Agent::firstOrCreate(['display_code' => $code], ['name' => $name]);

            foreach ($sides as $side) {
                $agent->sides()->firstOrCreate(['side' => $side]);
            }
        }

        $this->command?->info('Agents seeded.');
    }
}
