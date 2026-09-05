<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:warehouse.view', only: ['index', 'show']),
            new Middleware('permission:warehouse.create', only: ['create', 'store']),
            new Middleware('permission:warehouse.edit', only: ['edit', 'update']),
            new Middleware('permission:warehouse.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->withCount('stockLots')
            ->orderBy('name')
            ->get();

        return view('inventory.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('inventory.warehouses.create', [
            'kinds' => Warehouse::KINDS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $warehouse = Warehouse::create($data);

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', "Godown {$warehouse->code} created.");
    }

    public function show(Warehouse $warehouse): View
    {
        $warehouse->load(['stockLots' => fn ($q) => $q->with('product:id,name,item_group_code')->orderByDesc('received_at')]);

        return view('inventory.warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('inventory.warehouses.edit', [
            'warehouse' => $warehouse,
            'kinds' => Warehouse::KINDS,
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validated($request, $warehouse));

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', "Godown {$warehouse->code} updated.");
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->stockLots()->exists()) {
            return back()->with('warning', 'Cannot delete a godown that still has lots. Move or clear stock first.');
        }

        if (in_array($warehouse->code, ['MAIN', 'FG'], true)) {
            return back()->with('warning', 'Default godowns MAIN and FG cannot be deleted. Deactivate instead.');
        }

        $warehouse->delete();

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('success', 'Godown deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('warehouses', 'code')->ignore($warehouse?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(Warehouse::KINDS))],
            'is_active' => ['sometimes', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
