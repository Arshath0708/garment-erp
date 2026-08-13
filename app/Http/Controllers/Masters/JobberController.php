<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreSupplierRequest;
use App\Http\Requests\Masters\UpdateSupplierRequest;
use App\Models\Agent;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Designation;
use App\Models\Product;
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

class JobberController extends Controller implements HasMiddleware
{
    public function __construct(private readonly SupplierService $suppliers)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:jobber.view|supplier.view', only: ['index', 'show', 'checkCode', 'agents']),
            new Middleware('permission:jobber.create|supplier.create', only: ['create', 'store']),
            new Middleware('permission:jobber.edit|supplier.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:jobber.delete|supplier.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $jobbers = Supplier::query()
            ->with([
                'city:id,name',
                'state:id,name',
                'supplierType:id,name',
                'agent:id,name,display_code',
                'primaryContact:id,supplier_id,name,designation_id',
                'primaryContact.designation:id,name',
            ])
            ->withCount('categories')
            ->ofParty('jobber')
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($request->integer('category_id')))
            )
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.jobbers.index', [
            'jobbers'    => $jobbers,
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'filters'    => $request->only('search', 'status', 'category_id', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        // Change request #4 — "Default the Country to India". Falling back to
        // it here too, not just in the select's own default, so the State
        // list is pre-loaded for India rather than sitting empty until the
        // user touches the Country field.
        return view('masters.jobbers.create', $this->formData(
            'jobber',
            old('country_id') ? (int) old('country_id') : $this->indiaCountryId(),
            old('state_id') ? (int) old('state_id') : null,
        ));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['party_type'])) {
            $data['party_type'] = 'jobber';
        }

        $jobber = $this->suppliers->create($data);

        return redirect()
            ->route('masters.jobbers.index')
            ->with('success', "Jobber {$jobber->display_code} created successfully.");
    }

    public function show(Supplier $jobber): View
    {
        return view('masters.jobbers.show', [
            'jobber' => $jobber->load([
                'categories:id,name', 'products:id,name',
                'buyers:id,company_name,display_code',
                'contacts.designation', 'supplierType',
                'country', 'state', 'city', 'agent',
                'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Supplier $jobber): View
    {
        $countryId = old('country_id', $jobber->country_id);
        $stateId   = old('state_id', $jobber->state_id);

        return view('masters.jobbers.edit', $this->formData(
            old('party_type', $jobber->party_type),
            $countryId ? (int) $countryId : null,
            $stateId ? (int) $stateId : null,
        ) + [
            'jobber' => $jobber->load('categories:id', 'products:id', 'buyers:id', 'contacts'),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $jobber): RedirectResponse
    {
        $this->suppliers->update($jobber, $request->validated());

        return redirect()
            ->route('masters.jobbers.index')
            ->with('success', "Jobber {$jobber->display_code} updated successfully.");
    }

    public function destroy(Supplier $jobber): RedirectResponse
    {
        $check = $this->suppliers->canDelete($jobber);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $jobber->delete();

        return redirect()
            ->route('masters.jobbers.index')
            ->with('success', "Jobber {$jobber->display_code} deleted successfully.");
    }

    public function toggleStatus(Supplier $jobber): RedirectResponse
    {
        $jobber->update([
            'status' => $jobber->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Jobber {$jobber->display_code} marked {$jobber->status}.");
    }

    public function checkCode(Request $request): JsonResponse
    {
        $taken = Supplier::query()
            ->where('display_code', strtoupper(trim($request->string('value')->toString())))
            ->when($request->filled('ignore'), fn ($q) => $q->whereKeyNot($request->integer('ignore')))
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    public function agents(Request $request): JsonResponse
    {
        return response()->json(
            $this->agentsFor($request->string('party_type', 'jobber')->toString())
                ->map(fn (Agent $agent) => ['id' => $agent->id, 'name' => $agent->label])
                ->values()
        );
    }

    private function agentsFor(string $partyType): Collection
    {
        return Agent::active()
            ->whereIn('agent_type', Supplier::AGENT_SIDES[$partyType] ?? ['jobber'])
            ->orderBy('name')
            ->get();
    }

    private function formData(string $partyType, ?int $countryId = null, ?int $stateId = null): array
    {
        return [
            'partyTypes'    => Supplier::PARTY_TYPES,
            'deliveryModes' => Supplier::DELIVERY_MODES,
            'supplierTypes' => SupplierType::active()->orderBy('id')->pluck('name', 'id'),
            'supplierTypeFlags'  => SupplierType::active()->pluck('is_registered', 'id')
                ->map(fn ($registered) => (bool) $registered),

            'designations' => Designation::active()->orderBy('name')->pluck('name', 'id'),
            'categories'   => Category::active()->orderBy('name')->pluck('name', 'id'),
            'products'     => Product::active()->orderBy('name')->pluck('name', 'id'),
            // Change request #5, revised — the jobwork "client" is now a link
            // to the Buyer master rather than free text. get()->pluck('label')
            // because label is an accessor, not a column.
            'buyers'       => Buyer::active()->orderBy('company_name')->get()->pluck('label', 'id'),
            'agents'       => $this->agentsFor($partyType)->pluck('label', 'id'),
            'countries'    => Country::active()->orderBy('name')->get()->pluck('label', 'id'),

            // Change request #4 — "Default the Country to India".
            'indiaCountryId' => $this->indiaCountryId(),

            'states' => $countryId
                ? State::active()->where('country_id', $countryId)->orderBy('name')->pluck('name', 'id')
                : collect(),

            'cities' => $stateId
                ? City::active()->where('state_id', $stateId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ];
    }

    /**
     * Change request #4 — "Default the Country to India". Read by iso code
     * rather than name, same lookup GeoSeeder uses to seed it.
     */
    private function indiaCountryId(): ?int
    {
        return Country::where('iso_code', 'IN')->value('id');
    }
}
