<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

/**
 * The agents named on the client's own Buyer and Supplier example rows.
 *
 * Agent sheet col A is one side per agent, so an agent working both sides is
 * two records with two display codes — see Agent::TYPES.
 *
 * Idempotent on `display_code`, like every other seeder here.
 */
class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            // display_code, name, agent_type
            ['AG01', 'David',  'buyer'],
            ['AG02', 'James',  'buyer'],

            // Supplier and jobber side. Present specifically so the Buyer form
            // can be checked to NOT offer them — the col O filter is a
            // requirement, not a convenience, and an all-buyer-side list would
            // never show a bug.
            ['AG03', 'Suresh', 'supplier'],
            ['AG04', 'Ravi',   'jobber'],
        ];

        foreach ($agents as [$code, $name, $type]) {
            Agent::firstOrCreate(
                ['display_code' => $code],
                ['name' => $name, 'agent_type' => $type, 'status' => 'active'],
            );
        }

        $this->command?->info('Agents seeded.');
    }
}
