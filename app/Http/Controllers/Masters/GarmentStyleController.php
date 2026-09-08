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
            'style_number'     => ['required', 'string', 'max:50', 'unique:garment_styles,style_number'],
            'buyer_style_no'   => ['nullable', 'string', 'max:100'],
            'factory_style_no' => ['nullable', 'string', 'max:100'],
            'name'             => ['required', 'string', 'max:255'],
            'buyer_id'         => ['nullable', 'exists:buyers,id'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'season'           => ['nullable', 'string', 'max:100'],
            'color'            => ['nullable', 'string', 'max:100'],
            'design'           => ['nullable', 'string', 'max:255'],
            'fabric'           => ['nullable', 'string', 'max:255'],
            'sizes'            => ['nullable', 'string', 'max:255'],
            'target_qty'       => ['required', 'integer', 'min:0'],
            'tech_specs'       => ['nullable', 'string'],
            'status'           => ['required', 'string'],
            'logo'             => ['nullable', 'image', 'max:2048'],
            'materials'                => ['nullable', 'array'],
            'materials.*.product_id'   => ['nullable', 'exists:products,id'],
            'materials.*.qty_per_pc'   => ['nullable', 'numeric', 'min:0'],
            'materials.*.unit'         => ['nullable', 'string', 'max:20'],
            'materials.*.size_from'    => ['nullable', 'string', 'max:10'],
            'materials.*.size_to'      => ['nullable', 'string', 'max:10'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('styles/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $materials = $validated['materials'] ?? [];
        unset($validated['materials'], $validated['logo']);

        $this->applySizeBreakdown($request, $validated);

        $style = GarmentStyle::create($validated);
        $this->syncMaterials($style, $materials);

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style created successfully!');
    }

    public function show(GarmentStyle $style): View
    {
        $style->load(['buyer', 'category', 'productionOrders', 'materials.product', 'comments.user', 'costings', 'stock', 'bomApprover']);

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
            'style_number'     => ['required', 'string', 'max:50', Rule::unique('garment_styles', 'style_number')->ignore($style->id)],
            'buyer_style_no'   => ['nullable', 'string', 'max:100'],
            'factory_style_no' => ['nullable', 'string', 'max:100'],
            'name'             => ['required', 'string', 'max:255'],
            'buyer_id'         => ['nullable', 'exists:buyers,id'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'season'           => ['nullable', 'string', 'max:100'],
            'color'            => ['nullable', 'string', 'max:100'],
            'design'           => ['nullable', 'string', 'max:255'],
            'fabric'           => ['nullable', 'string', 'max:255'],
            'sizes'            => ['nullable', 'string', 'max:255'],
            'target_qty'       => ['required', 'integer', 'min:0'],
            'tech_specs'       => ['nullable', 'string'],
            'status'           => ['required', 'string'],
            'logo'             => ['nullable', 'image', 'max:2048'],
            'materials'                => ['nullable', 'array'],
            'materials.*.product_id'   => ['nullable', 'exists:products,id'],
            'materials.*.qty_per_pc'   => ['nullable', 'numeric', 'min:0'],
            'materials.*.unit'         => ['nullable', 'string', 'max:20'],
            'materials.*.size_from'    => ['nullable', 'string', 'max:10'],
            'materials.*.size_to'      => ['nullable', 'string', 'max:10'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('styles/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $this->applySizeBreakdown($request, $validated);

        $materials = $validated['materials'] ?? [];
        unset($validated['materials'], $validated['logo']);

        $style->update($validated);
        $this->syncMaterials($style, $materials);

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style updated successfully!');
    }

    public function storeComment(Request $request, GarmentStyle $style): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $style->comments()->create([
            'user_id'   => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'System User',
            'comment'   => $validated['comment'],
        ]);

        return redirect()->route('masters.styles.show', $style->id)->with('success', 'Style comment added successfully!');
    }

    public function approveBom(GarmentStyle $style): RedirectResponse
    {
        $style->load('materials.product');

        $style->update([
            'bom_approved_at' => now(),
            'bom_approved_by' => auth()->id(),
        ]);

        $style->bomSnapshots()->updateOrCreate(
            [
                'garment_style_id' => $style->id,
                'version'          => (int) ($style->bom_version ?: 1),
            ],
            [
                'materials'   => $style->materials->map(fn ($line) => [
                    'product_id' => $line->product_id,
                    'name'       => $line->product?->name,
                    'qty_per_pc' => (float) $line->qty_per_pc,
                    'unit'       => $line->unit,
                    'size_from'  => $line->size_from,
                    'size_to'    => $line->size_to,
                ])->values()->all(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]
        );

        return redirect()
            ->route('masters.styles.show', $style)
            ->with('success', "BOM v{$style->bom_version} approved.");
    }

    public function destroy(GarmentStyle $style): RedirectResponse
    {
        $style->delete();

        return redirect()->route('masters.styles.index')->with('success', 'Garment Style deleted successfully!');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applySizeBreakdown(Request $request, array &$validated): void
    {
        if (! $request->has('size_names') || ! is_array($request->input('size_names'))) {
            return;
        }

        $sizeNames = $request->input('size_names', []);
        $sizeQtys = $request->input('size_qtys', []);
        $formattedSizes = [];
        $totalQty = 0;

        foreach ($sizeNames as $i => $name) {
            $trimmedName = trim((string) $name);
            $qty = (int) ($sizeQtys[$i] ?? 0);
            if ($trimmedName === '') {
                continue;
            }
            $formattedSizes[] = $qty > 0 ? "{$trimmedName} ({$qty} pcs)" : $trimmedName;
            $totalQty += $qty;
        }

        if ($formattedSizes !== []) {
            $validated['sizes'] = implode(', ', $formattedSizes);
        }
        if ($totalQty > 0) {
            $validated['target_qty'] = $totalQty;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncMaterials(GarmentStyle $style, array $rows): void
    {
        $wasApproved = $style->isBomApproved();
        $before = $this->materialsFingerprint($style);

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
                'size_from'  => ($row['size_from'] ?? null) ?: null,
                'size_to'    => ($row['size_to'] ?? null) ?: null,
                'sort_order' => $order++,
            ]);
        }

        if ($wasApproved && $before !== $this->materialsFingerprint($style->fresh())) {
            $style->update([
                'bom_version'     => ((int) $style->bom_version) + 1,
                'bom_approved_at' => null,
                'bom_approved_by' => null,
            ]);
        }
    }

    private function materialsFingerprint(GarmentStyle $style): string
    {
        $rows = $style->materials()
            ->orderBy('sort_order')
            ->get(['product_id', 'qty_per_pc', 'unit', 'size_from', 'size_to']);

        return md5(json_encode($rows->map(fn ($row) => [
            (int) $row->product_id,
            round((float) $row->qty_per_pc, 4),
            (string) $row->unit,
            (string) $row->size_from,
            (string) $row->size_to,
        ])->values()->all()));
    }
}
