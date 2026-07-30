<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which side of the business an agent works — Agent sheet col A, "drop down
 * menu with a supplier side, buyer side and jobber side".
 *
 * A row per side rather than an enum column on `agents`, because one agent can
 * work more than one side. Agent::scopeOnSide() reads it.
 */
class AgentSide extends Model
{
    protected $fillable = ['agent_id', 'side'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
