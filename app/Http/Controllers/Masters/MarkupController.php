<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreMarkupRequest;
use App\Http\Requests\Masters\UpdateMarkupRequest;
use App\Models\Buyer;
use App\Models\Markup;
use App\Models\Supplier;
use App\Services\Masters\MarkupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MarkupController extends Controller implements HasMiddleware
{
    public function __construct(private readonly MarkupService $markups)
    {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:markup.view', only: [
                'index', 'show', 'supplierDiscount', 'supplierAgentCommission', 'buyerAgentCommission',
            ]),
            new Middleware('permission:markup.create', only: ['create', 'store']),
            new Middleware('permission:markup.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:markup.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $markups = Markup::query()
            ->with(['supplier:id,display_code,company_name,discount_percent', 'buyer:id,display_code,company_name'])
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(10)
            ->withQueryString();

        return view('masters.markups.index', [
            'markups' => $markups,
            'filters' => $request->only('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        return view('masters.markups.create', array_merge($this->formData(), [
            'markup' => null,
        ]));
    }

    public function store(StoreMarkupRequest $request): RedirectResponse
    {
        $markup = $this->markups->create($request->validated());

        return redirect()
            ->route('masters.markups.index')
            ->with('success', "Markup rule for {$markup->supplier->company_name} → {$markup->buyer->company_name} created successfully.");
    }

    public function show(Markup $markup): View
    {
        $markup->load(['supplier.agent', 'buyer.agent', 'creator', 'updater']);

        return view('masters.markups.show', [
            'markup'  => $markup,
            'preview' => $this->markups->preview($markup),
        ]);
    }

    public function edit(Markup $markup): View
    {
        return view('masters.markups.edit', array_merge($this->formData($markup), [
            'markup' => $markup->load(['supplier.agent', 'buyer.agent']),
        ]));
    }

    public function update(UpdateMarkupRequest $request, Markup $markup): RedirectResponse
    {
        $this->markups->update($markup, $request->validated());

        return redirect()
            ->route('masters.markups.index')
            ->with('success', 'Markup rule updated successfully.');
    }

    public function destroy(Markup $markup): RedirectResponse
    {
        $check = $this->markups->canDelete($markup);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $markup->delete();

        return redirect()
            ->route('masters.markups.index')
            ->with('success', 'Markup rule deleted successfully.');
    }

    public function toggleStatus(Markup $markup): RedirectResponse
    {
        $markup->update([
            'status' => $markup->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Markup rule marked {$markup->status}.");
    }

    /**
     * The chosen supplier's discount, for the read-only Discount % field.
     *
     * The field is filled in by the server on load and by this endpoint when
     * the supplier changes, so the profit lines on the form stay truthful
     * without a page reload. Guarded by markup.view — it exposes nothing the
     * Supplier master does not already show to the same people.
     */
    public function supplierDiscount(Request $request): JsonResponse
    {
        $supplier = Supplier::query()
            ->select('id', 'discount_percent')
            ->find($request->integer('supplier_id'));

        return response()->json([
            'discount_percent' => $supplier ? (float) ($supplier->discount_percent ?? 0) : null,
        ]);
    }

    /**
     * The chosen supplier's own agent and their negotiated commission, for
     * the read-only Agent Commission fields. Same treatment as
     * supplierDiscount() — filled on load and refreshed on change so the
     * form never shows a figure that belongs to the previously selected
     * supplier.
     */
    public function supplierAgentCommission(Request $request): JsonResponse
    {
        $supplier = Supplier::query()
            ->select('id', 'agent_id', 'agent_commission_type', 'agent_commission_value')
            ->with('agent:id,name,display_code')
            ->find($request->integer('supplier_id'));

        return response()->json([
            'agent'      => $supplier?->agent?->label,
            'commission' => $supplier?->agent_commission_label,
        ]);
    }

    /** The chosen buyer's own agent and their negotiated commission. */
    public function buyerAgentCommission(Request $request): JsonResponse
    {
        $buyer = Buyer::query()
            ->select('id', 'agent_id', 'agent_commission_type', 'agent_commission_value')
            ->with('agent:id,name,display_code')
            ->find($request->integer('buyer_id'));

        return response()->json([
            'agent'      => $buyer?->agent?->label,
            'commission' => $buyer?->agent_commission_label,
        ]);
    }

    /**
     * Dropdown sources.
     *
     * Inactive parties are excluded so a retired supplier cannot be given a new
     * rule — but the one already on the record being edited is offered back,
     * or saving an unrelated change would silently fail validation.
     *
     * @return array<string, mixed>
     */
    private function formData(?Markup $markup = null): array
    {
        return [
            'suppliers' => $this->party(Supplier::query(), $markup?->supplier_id),
            'buyers'    => $this->party(Buyer::query(), $markup?->buyer_id),

            // Every supplier's discount, so the form can fill the read-only
            // field on first paint without a round trip.
            'discounts' => Supplier::query()
                ->pluck('discount_percent', 'id')
                ->map(fn ($value) => (float) ($value ?? 0))
                ->all(),

            // Same idea for each party's own agent commission — the form
            // shows it without a round trip, and supplierAgentCommission() /
            // buyerAgentCommission() are only the fallback for a party added
            // since the page was rendered.
            'supplierAgentCommissions' => $this->agentCommissions(Supplier::query()),
            'buyerAgentCommissions'    => $this->agentCommissions(Buyer::query()),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<int, array{agent: ?string, commission: ?string}>
     */
    private function agentCommissions(\Illuminate\Database\Eloquent\Builder $query): array
    {
        return $query->with('agent:id,name,display_code')
            ->get(['id', 'agent_id', 'agent_commission_type', 'agent_commission_value'])
            ->mapWithKeys(fn ($party) => [$party->id => [
                'agent'      => $party->agent?->label,
                'commission' => $party->agent_commission_label,
            ]])
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function party(\Illuminate\Database\Eloquent\Builder $query, ?int $keepId)
    {
        return $query
            ->where(fn ($q) => $q->where('status', 'active')->orWhere('id', $keepId))
            ->orderBy('company_name')
            ->get(['id', 'display_code', 'company_name'])
            ->mapWithKeys(fn ($row) => [$row->id => "{$row->display_code} — {$row->company_name}"]);
    }
}
