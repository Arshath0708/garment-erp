<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StyleCostingRequest;
use App\Models\GarmentStyle;
use App\Models\StyleCosting;
use App\Services\Masters\StyleCostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;

class StyleCostingController extends Controller implements HasMiddleware
{
    public function __construct(private readonly StyleCostingService $costings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:style-costing.view', only: ['index', 'show']),
            new Middleware('permission:style-costing.create', only: ['create', 'store']),
            new Middleware('permission:style-costing.edit', only: ['edit', 'update']),
            new Middleware('permission:style-costing.approve', only: ['approve']),
            new Middleware('permission:style-costing.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $query = StyleCosting::query()
            ->with(['garmentStyle', 'buyer'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('costing_num', 'like', $term)
                    ->orWhereHas('garmentStyle', fn ($s) => $s->where('style_number', 'like', $term)->orWhere('name', 'like', $term));
            });
        }

        return view('style-costings.index', [
            'costings' => $query->paginate(20)->withQueryString(),
            'filters'  => $request->only('search', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        $styleId = $request->integer('style_id') ?: null;
        $selectedStyle = $styleId
            ? GarmentStyle::query()->with('materials.product')->find($styleId)
            : null;

        return view('style-costings.create', array_merge($this->formData(), [
            'selectedStyle' => $selectedStyle,
            'lines'         => $selectedStyle ? $this->costings->previewLines($selectedStyle) : [],
        ]));
    }

    public function store(StyleCostingRequest $request): RedirectResponse
    {
        $costing = $this->costings->create($request->validated());

        return redirect()
            ->route('style-costings.show', $costing)
            ->with('success', "Costing {$costing->costing_num} saved as Draft. Approve it to sign “this style costs ₹X”.");
    }

    public function show(StyleCosting $styleCosting): View
    {
        $styleCosting->load(['garmentStyle', 'buyer', 'lines', 'approvedByUser']);

        return view('style-costings.show', ['costing' => $styleCosting]);
    }

    public function edit(StyleCosting $styleCosting): View|RedirectResponse
    {
        if ($styleCosting->isApproved()) {
            return redirect()
                ->route('style-costings.show', $styleCosting)
                ->with('warning', 'Approved costing cannot be edited. Make a new sheet if the cost has changed.');
        }

        $styleCosting->load(['lines', 'garmentStyle']);

        return view('style-costings.edit', array_merge($this->formData(), [
            'costing'       => $styleCosting,
            'selectedStyle' => $styleCosting->garmentStyle,
            'lines'         => $styleCosting->lines->map(fn ($line) => [
                'product_id'  => $line->product_id,
                'description' => $line->description,
                'item_kind'   => $line->item_kind,
                'qty_per_pc'  => (float) $line->qty_per_pc,
                'unit'        => $line->unit,
                'rate'        => (float) $line->rate,
            ])->all(),
        ]));
    }

    public function update(StyleCostingRequest $request, StyleCosting $styleCosting): RedirectResponse
    {
        try {
            $this->costings->update($styleCosting, $request->validated());
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('style-costings.show', $styleCosting)
            ->with('success', "Costing {$styleCosting->costing_num} updated.");
    }

    public function destroy(StyleCosting $styleCosting): RedirectResponse
    {
        try {
            $num = $styleCosting->costing_num;
            $this->costings->delete($styleCosting);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('style-costings.index')
            ->with('success', "Costing {$num} deleted.");
    }

    public function approve(StyleCosting $styleCosting): RedirectResponse
    {
        $this->costings->approve($styleCosting);

        return back()->with('success', "Costing {$styleCosting->costing_num} approved. This style costs ₹".number_format((float) $styleCosting->fresh()->total_cost_per_pc, 2).' per piece.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'styles' => GarmentStyle::query()
                ->with('buyer')
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('style_number')
                ->get(),
        ];
    }
}
