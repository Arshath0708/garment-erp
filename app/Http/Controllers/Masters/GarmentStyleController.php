<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\GarmentStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GarmentStyleController extends Controller
{
    public function index(Request $request): View
    {
        $query = GarmentStyle::query()->with(['buyer', 'category'])->orderByDesc('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('style_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('fabric', 'like', "%{$search}%");
            });
        }

        $styles = $query->paginate(15);

        return view('masters.styles.index', compact('styles'));
    }

    public function create(): View
    {
        $buyers = Buyer::query()->whereIn('status', ['active', 'Active'])->orderBy('company_name')->get();
        $categories = Category::query()->whereIn('status', ['active', 'Active'])->orderBy('name')->get();


        return view('masters.styles.create', compact('buyers', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'style_number' => ['required', 'string', 'max:50', 'unique:garment_styles,style_number'],
            'name'         => ['required', 'string', 'max:255'],
            'buyer_id'     => ['nullable', 'exists:buyers,id'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'season'       => ['nullable', 'string', 'max:100'],
            'color'        => ['nullable', 'string', 'max:100'],
            'design'       => ['nullable', 'string', 'max:255'],
            'fabric'       => ['nullable', 'string', 'max:255'],
            'sizes'        => ['nullable', 'string', 'max:255'],
            'target_qty'   => ['required', 'integer', 'min:0'],
            'tech_specs'   => ['nullable', 'string'],
            'status'       => ['required', 'string'],
            'logo'         => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->has('size_names') && is_array($request->input('size_names'))) {
            $sizeNames = $request->input('size_names', []);
            $sizeQtys = $request->input('size_qtys', []);
            $formattedSizes = [];
            $totalQty = 0;

            foreach ($sizeNames as $i => $name) {
                $trimmedName = trim((string) $name);
                $qty = (int) ($sizeQtys[$i] ?? 0);
                if ($trimmedName !== '') {
                    $formattedSizes[] = $qty > 0 ? "{$trimmedName} ({$qty} pcs)" : $trimmedName;
                    $totalQty += $qty;
                }
            }

            if (!empty($formattedSizes)) {
                $validated['sizes'] = implode(', ', $formattedSizes);
            }
            if ($totalQty > 0) {
                $validated['target_qty'] = $totalQty;
            }
        }

        GarmentStyle::create($validated);

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style created successfully!');
    }

    public function show(GarmentStyle $style): View
    {
        $style->load(['buyer', 'category', 'productionOrders']);

        return view('masters.styles.show', compact('style'));
    }

    public function edit(GarmentStyle $style): View
    {
        $buyers = Buyer::query()->whereIn('status', ['active', 'Active'])->orderBy('company_name')->get();
        $categories = Category::query()->whereIn('status', ['active', 'Active'])->orderBy('name')->get();


        return view('masters.styles.edit', compact('style', 'buyers', 'categories'));
    }

    public function update(Request $request, GarmentStyle $style): RedirectResponse
    {
        $validated = $request->validate([
            'style_number' => ['required', 'string', 'max:50', Rule::unique('garment_styles', 'style_number')->ignore($style->id)],
            'name'         => ['required', 'string', 'max:255'],
            'buyer_id'     => ['nullable', 'exists:buyers,id'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'season'       => ['nullable', 'string', 'max:100'],
            'color'        => ['nullable', 'string', 'max:100'],
            'design'       => ['nullable', 'string', 'max:255'],
            'fabric'       => ['nullable', 'string', 'max:255'],
            'sizes'        => ['nullable', 'string', 'max:255'],
            'target_qty'   => ['required', 'integer', 'min:0'],
            'tech_specs'   => ['nullable', 'string'],
            'status'       => ['required', 'string'],
            'logo'         => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('styles/logos', 'public');
            $validated['logo_path'] = $path;
        }

        if ($request->has('size_names') && is_array($request->input('size_names'))) {
            $sizeNames = $request->input('size_names', []);
            $sizeQtys = $request->input('size_qtys', []);
            $formattedSizes = [];
            $totalQty = 0;

            foreach ($sizeNames as $i => $name) {
                $trimmedName = trim((string) $name);
                $qty = (int) ($sizeQtys[$i] ?? 0);
                if ($trimmedName !== '') {
                    $formattedSizes[] = $qty > 0 ? "{$trimmedName} ({$qty} pcs)" : $trimmedName;
                    $totalQty += $qty;
                }
            }

            if (!empty($formattedSizes)) {
                $validated['sizes'] = implode(', ', $formattedSizes);
            }
            if ($totalQty > 0) {
                $validated['target_qty'] = $totalQty;
            }
        }

        $style->update($validated);


        return redirect()->route('masters.styles.index')->with('success', 'Garment Style updated successfully!');
    }

    public function destroy(GarmentStyle $style): RedirectResponse
    {
        $style->delete();

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style deleted successfully!');
    }
}
