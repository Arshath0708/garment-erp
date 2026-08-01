<?php

namespace App\Http\Requests\Masters;

class StoreAgentRequest extends AgentRequest
{
    protected function permission(): string
    {
        return 'agent.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
