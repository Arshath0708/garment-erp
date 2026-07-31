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
            $categories = $data['categories'] ?? [];
            unset($data['categories']);

            $agent = Agent::create($data);
            $agent->categories()->sync($categories);

            return $agent;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Agent $agent, array $data): Agent
    {
        return DB::transaction(function () use ($agent, $data) {
            $categories = $data['categories'] ?? [];
            unset($data['categories']);

            $agent->update($data);
            $agent->categories()->sync($categories);

            return $agent->refresh();
        });
    }

    /**
     * Checks if the agent can be deleted safely.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Agent $agent): array
    {
        // Currently nothing references agents. This is future-ready.
        return ['allowed' => true, 'reason' => null];
    }
}
