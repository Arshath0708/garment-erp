<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreBuyerRequest;
use App\Http\Requests\Masters\UpdateBuyerRequest;
use App\Models\Agent;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Incoterm;
use App\Models\PaymentTerm;
use App\Models\Port;
use App\Models\ShipmentMethod;
use App\Models\State;
use App\Services\Masters\BuyerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BuyerController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BuyerService $buyers)
    {
    }

    /**
     * Per-action permissions live here rather than on the route, so a new
     * action cannot be added without also deciding what guards it.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:buyer.view', only: ['index', 'show', 'checkCode']),
            new Middleware('permission:buyer.create', only: ['create', 'store']),
            new Middleware('permission:buyer.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:buyer.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $buyers = Buyer::query()
            ->with(['country:id,name,iso_code', 'city:id,name', 'agent:id,name,display_code'])
            ->withCount('categories')
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($request->integer('category_id')))
            )
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.buyers.index', [
            'buyers'     => $buyers,
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'filters'    => $request->only('search', 'status', 'category_id', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        // old() rather than null: a failed validation round-trip must come back
        // with the state and city lists still populated for the country the
        // user had picked, or their choices appear to have been thrown away.
        return view('masters.buyers.create', $this->formData(
            old('country_id') ? (int) old('country_id') : null,
            old('state_id') ? (int) old('state_id') : null,
        ));
    }

    public function store(StoreBuyerRequest $request): RedirectResponse
    {
        $buyer = $this->buyers->create($request->validated());

        return redirect()
            ->route('masters.buyers.index')
            ->with('success', "Buyer {$buyer->display_code} created successfully.");
    }

    public function show(Buyer $buyer): View
    {
        return view('masters.buyers.show', [
            'buyer' => $buyer->load([
                'categories:id,name', 'cartonMarkings', 'country', 'state', 'city', 'port',
                'agent', 'paymentTerm', 'incoterm', 'shipmentMethod', 'currency',
                'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Buyer $buyer): View
    {
        return view('masters.buyers.edit', $this->formData(
            old('country_id', $buyer->country_id) ? (int) old('country_id', $buyer->country_id) : null,
            old('state_id', $buyer->state_id) ? (int) old('state_id', $buyer->state_id) : null,
        ) + [
            'buyer' => $buyer->load('categories:id', 'cartonMarkings'),
        ]);
    }

    public function update(UpdateBuyerRequest $request, Buyer $buyer): RedirectResponse
    {
        $this->buyers->update($buyer, $request->validated());

        return redirect()
            ->route('masters.buyers.index')
            ->with('success', "Buyer {$buyer->display_code} updated successfully.");
    }

    public function destroy(Buyer $buyer): RedirectResponse
    {
        $check = $this->buyers->canDelete($buyer);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $buyer->delete();

        return redirect()
            ->route('masters.buyers.index')
            ->with('success', "Buyer {$buyer->display_code} deleted successfully.");
    }

    public function toggleStatus(Buyer $buyer): RedirectResponse
    {
        $buyer->update([
            'status' => $buyer->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Buyer {$buyer->display_code} marked {$buyer->status}.");
    }

    /**
     * Sheet col A: "it should tell me if a certain code is used already".
     * Called from the form as the user types.
     *
     * A convenience only. The unique index is what actually enforces it — two
     * users typing BUY01 at the same moment both get "available" here, and the
     * second insert is the one that fails.
     */
    public function checkCode(Request $request): JsonResponse
    {
        $taken = Buyer::query()
            ->where('display_code', strtoupper(trim($request->string('value')->toString())))
            ->when($request->filled('ignore'), fn ($q) => $q->whereKeyNot($request->integer('ignore')))
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    /**
     * Dropdown sources shared by the create and edit forms.
     *
     * Only active rows: a retired port or incoterm must not be selectable on a
     * new buyer, while buyers already pointing at it keep rendering.
     *
     * State and city are the two dependent lists. They are rendered server-side
     * for whatever country and state are already chosen, so an edit form is
     * correct before any JavaScript runs and a user without it can still read
     * the saved value. GeoController takes over once a parent is changed.
     *
     * @return array<string, mixed>
     */
    private function formData(?int $countryId = null, ?int $stateId = null): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),

            /*
             * Col O: "only those which are a part of buyer side selected in
             * agent master should show here". The same filter is re-applied as
             * a validation rule in BuyerRequest — this select narrows the list,
             * it does not enforce it.
             */
            'agents' => Agent::active()->onSide('buyer')->orderBy('name')->get()->pluck('label', 'id'),

            'countries'       => Country::active()->orderBy('name')->get()->pluck('label', 'id'),

            'states' => $countryId
                ? State::active()->where('country_id', $countryId)->orderBy('name')->pluck('name', 'id')
                : collect(),

            'cities' => $stateId
                ? City::active()->where('state_id', $stateId)->orderBy('name')->pluck('name', 'id')
                : collect(),

            'ports'           => Port::active()->with('country:id,iso_code')->orderBy('name')->get()->pluck('label', 'id'),
            'paymentTerms'    => PaymentTerm::active()->forSide('buyer')->orderBy('name')->pluck('name', 'id'),
            'incoterms'       => Incoterm::active()->orderBy('code')->get()->pluck('label', 'id'),
            'shipmentMethods' => ShipmentMethod::active()->orderBy('name')->pluck('name', 'id'),
            'currencies'      => Currency::active()->orderBy('iso_code')->get()->pluck('label', 'id'),
        ];
    }
}
