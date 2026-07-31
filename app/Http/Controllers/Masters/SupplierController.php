<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreSupplierRequest;
use App\Http\Requests\Masters\UpdateSupplierRequest;
use App\Models\Agent;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Designation;
use App\Models\State;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Services\Masters\SupplierService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    public function __construct(private readonly SupplierService $suppliers)
    {
    }

    /**
     * Per-action permissions live here rather than on the route, so a new
     * action cannot be added without also deciding what guards it.
     *
     * DATABASE_SCHEMA.md §5 anticipates a separate `jobber.*` set once the
     * Jobber screen is split out. Until then this is one module — which is also
     * how config/permissions.php labels it, "Supplier (Jobber)".
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:supplier.view', only: ['index', 'show', 'checkCode', 'agents']),
            new Middleware('permission:supplier.create', only: ['create', 'store']),
            new Middleware('permission:supplier.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:supplier.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->with([
                'city:id,name',
                'state:id,name',
                'supplierType:id,name',
                'agent:id,name,display_code',
                'primaryContact:id,supplier_id,name,designation_id',
                'primaryContact.designation:id,name',
            ])
            ->withCount('categories')
            ->ofParty($request->string('party_type')->toString())
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($request->integer('category_id')))
            )
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.suppliers.index', [
            'suppliers'  => $suppliers,
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'filters'    => $request->only('search', 'status', 'party_type', 'category_id', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        // old() rather than null: a failed validation round-trip must come back
        // with the state, city and agent lists still populated for what the
        // user had picked, or their choices appear to have been thrown away.
        return view('masters.suppliers.create', $this->formData(
            old('party_type', 'supplier'),
            old('country_id') ? (int) old('country_id') : null,
            old('state_id') ? (int) old('state_id') : null,
        ));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->suppliers->create($request->validated());

        return redirect()
            ->route('masters.suppliers.index')
            ->with('success', "Supplier {$supplier->display_code} created successfully.");
    }

    public function show(Supplier $supplier): View
    {
        return view('masters.suppliers.show', [
            'supplier' => $supplier->load([
                'categories:id,name', 'contacts.designation', 'supplierType',
                'country', 'state', 'city', 'agent',
                'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        $countryId = old('country_id', $supplier->country_id);
        $stateId   = old('state_id', $supplier->state_id);

        return view('masters.suppliers.edit', $this->formData(
            old('party_type', $supplier->party_type),
            $countryId ? (int) $countryId : null,
            $stateId ? (int) $stateId : null,
        ) + [
            'supplier' => $supplier->load('categories:id', 'contacts'),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->validated());

        return redirect()
            ->route('masters.suppliers.index')
            ->with('success', "Supplier {$supplier->display_code} updated successfully.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $check = $this->suppliers->canDelete($supplier);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $supplier->delete();

        return redirect()
            ->route('masters.suppliers.index')
            ->with('success', "Supplier {$supplier->display_code} deleted successfully.");
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        $supplier->update([
            'status' => $supplier->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Supplier {$supplier->display_code} marked {$supplier->status}.");
    }

    /**
     * Sheet col A: "it should tell me if a certain code is used already".
     * Called from the form as the user types.
     *
     * A convenience only. The unique index is what actually enforces it — two
     * users typing SUP01 at the same moment both get "available" here, and the
     * second insert is the one that fails.
     */
    public function checkCode(Request $request): JsonResponse
    {
        $taken = Supplier::query()
            ->where('display_code', strtoupper(trim($request->string('value')->toString())))
            ->when($request->filled('ignore'), fn ($q) => $q->whereKeyNot($request->integer('ignore')))
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    /**
     * Col X, reloaded when the party type changes.
     *
     * The sheet says "of supplier side selected in agent master should show
     * here", which is the answer for a trading supplier. Once party_type exists
     * a jobber's agent has to come from the jobber side instead, so the list is
     * a function of the party type rather than a constant — which makes it the
     * same shape as the country/state cascade, and it reuses the same wiring in
     * app.js. Supplier::AGENT_SIDES is the single source for both this list and
     * the rule in SupplierRequest.
     */
    public function agents(Request $request): JsonResponse
    {
        return response()->json(
            $this->agentsFor($request->string('party_type')->toString())
                ->map(fn (Agent $agent) => ['id' => $agent->id, 'name' => $agent->label])
                ->values()
        );
    }

    /**
     * Active agents on the sides this party type is allowed to use.
     *
     * @return Collection<int, Agent>
     */
    private function agentsFor(string $partyType): Collection
    {
        return Agent::active()
            ->whereIn('agent_type', Supplier::AGENT_SIDES[$partyType] ?? ['supplier'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Dropdown sources shared by the create and edit forms.
     *
     * Only active rows: a retired designation or supplier type must not be
     * selectable on a new supplier, while suppliers already pointing at one
     * keep rendering.
     *
     * State, city and agent are the three dependent lists. All are rendered
     * server-side for whatever parent is already chosen, so an edit form is
     * correct before any JavaScript runs and a user without it can still read
     * the saved value. The cascade endpoints take over once a parent changes.
     *
     * @return array<string, mixed>
     */
    private function formData(string $partyType, ?int $countryId = null, ?int $stateId = null): array
    {
        return [
            'partyTypes'    => Supplier::PARTY_TYPES,
            'deliveryModes' => Supplier::DELIVERY_MODES,

            // Col D. The registered flag goes to the browser as well — col E's
            // GST field is shown or hidden by it, and the form cannot ask the
            // server which of the types counts as registered on every keystroke.
            'supplierTypes'      => SupplierType::active()->orderBy('id')->pluck('name', 'id'),
            'supplierTypeFlags'  => SupplierType::active()->pluck('is_registered', 'id')
                ->map(fn ($registered) => (bool) $registered),

            'designations' => Designation::active()->orderBy('name')->pluck('name', 'id'),
            'categories'   => Category::active()->orderBy('name')->pluck('name', 'id'),

            // Col X — filtered by party type; see agents() above.
            'agents' => $this->agentsFor($partyType)->pluck('label', 'id'),

            'countries' => Country::active()->orderBy('name')->get()->pluck('label', 'id'),

            'states' => $countryId
                ? State::active()->where('country_id', $countryId)->orderBy('name')->pluck('name', 'id')
                : collect(),

            'cities' => $stateId
                ? City::active()->where('state_id', $stateId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ];
    }
}
