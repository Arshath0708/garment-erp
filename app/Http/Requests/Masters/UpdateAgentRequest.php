<?php

namespace App\Http\Requests\Masters;

class UpdateAgentRequest extends AgentRequest
{
    protected function permission(): string
    {
        return 'agent.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('agent')->id;
    }
}
