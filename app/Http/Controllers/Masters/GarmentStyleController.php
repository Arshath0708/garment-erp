<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\Product;
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


        $products = Product::query()->where('status', 'active')->orderBy('name')->get();

        return view('masters.styles.create', compact('buyers', 'categories', 'products'));
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
            'materials'                => ['nullable', 'array'],
            'materials.*.product_id'   => ['nullable', 'exists:products,id'],
            'materials.*.qty_per_pc'   => ['nullable', 'numeric', 'min:0'],
            'materials.*.unit'         => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('styles/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $materials = $validated['materials'] ?? [];
        unset($validated['materials'], $validated['logo']);

        $style = GarmentStyle::create($validated);
        $this->syncMaterials($style, $materials);

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style created successfully!');
    }

    public function show(GarmentStyle $style): View
    {
        $style->load(['buyer', 'category', 'productionOrders', 'materials.product']);

        return view('masters.styles.show', compact('style'));
    }

    public function edit(GarmentStyle $style): View
    {
        $buyers = Buyer::query()->whereIn('status', ['active', 'Active'])->orderBy('company_name')->get();
        $categories = Category::query()->whereIn('status', ['active', 'Active'])->orderBy('name')->get();
        $products = Product::query()->where('status', 'active')->orderBy('name')->get();
        $style->load('materials');

        return view('masters.styles.edit', compact('style', 'buyers', 'categories', 'products'));
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
            'materials'                => ['nullable', 'array'],
            'materials.*.product_id'   => ['nullable', 'exists:products,id'],
            'materials.*.qty_per_pc'   => ['nullable', 'numeric', 'min:0'],
            'materials.*.unit'         => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('styles/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $materials = $validated['materials'] ?? [];
        unset($validated['materials'], $validated['logo']);

        $style->update($validated);
        $this->syncMaterials($style, $materials);

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style updated successfully!');
    }

    public function destroy(GarmentStyle $style): RedirectResponse
    {
        $style->delete();

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style deleted successfully!');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncMaterials(GarmentStyle $style, array $rows): void
    {
        $style->materials()->delete();
        $order = 0;
        foreach (array_values($rows) as $row) {
            if (blank($row['product_id'] ?? null)) {
                continue;
            }
            $style->materials()->create([
                'product_id' => $row['product_id'],
                'qty_per_pc' => $row['qty_per_pc'] ?? 0,
                'unit'       => $row['unit'] ?? null,
                'sort_order' => $order++,
            ]);
        }
    }
}
