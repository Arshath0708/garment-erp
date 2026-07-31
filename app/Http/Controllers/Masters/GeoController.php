<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the cascading Country -> State -> City dropdowns on the Buyer form.
 *
 * Reference data only — no per-module permission beyond the `auth` middleware
 * the route group already applies. A list of Indian states is not information
 * a logged-in user could be denied; gating it behind buyer.view would also
 * break the moment the Supplier and Agent forms reuse these endpoints.
 *
 * The initial options are rendered server-side by BuyerController, so an edit
 * form shows the right state and city before any JavaScript runs. These
 * endpoints are only hit when the user actually changes a parent.
 */
class GeoController extends Controller
{
    /**
     * States belonging to one country.
     */
    public function states(Request $request): JsonResponse
    {
        // No country means no states — not every state in the database.
        if (! $request->filled('country_id')) {
            return response()->json([]);
        }

        return response()->json(
            State::query()
                ->active()
                ->where('country_id', $request->integer('country_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    /**
     * Cities belonging to one state.
     */
    public function cities(Request $request): JsonResponse
    {
        if (! $request->filled('state_id')) {
            return response()->json([]);
        }

        return response()->json(
            City::query()
                ->active()
                ->where('state_id', $request->integer('state_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
