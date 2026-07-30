<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreProductRequest;
use App\Http\Requests\Masters\UpdateProductRequest;
use App\Models\CalculationBasis;
use App\Models\Category;
use App\Models\GstRate;
use App\Models\PriceBand;
use App\Models\Product;
use App\Services\Masters\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ProductService $products)
    {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:product.view', only: ['index', 'show', 'checkCode']),
            new Middleware('permission:product.create', only: ['create', 'store']),
            new Middleware('permission:product.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:product.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,name', 'gstRate:id,rate'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.products.index', [
            'products'   => $products,
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'filters'    => $request->only('search', 'status', 'category_id', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        return view('masters.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return redirect()
            ->route('masters.products.index')
            ->with('success', "Product {$product->item_group_code} created successfully.");
    }

    public function show(Product $product): View
    {
        return view('masters.products.show', [
            'product' => $product->load([
                'category', 'priceBand', 'gstRate',
                'incentives.calculationBasis', 'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('masters.products.edit', $this->formData() + [
            'product' => $product->load('incentives'),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return redirect()
            ->route('masters.products.index')
            ->with('success', "Product {$product->item_group_code} updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $check = $this->products->canDelete($product);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $product->delete();

        return redirect()
            ->route('masters.products.index')
            ->with('success', "Product {$product->item_group_code} deleted successfully.");
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Product {$product->item_group_code} marked {$product->status}.");
    }

    /**
     * Sheet cols B and C: "incase a code / name is already taken I should get
     * an alert here". Called from the form as the user types.
     *
     * This is a convenience only. The unique index is what actually enforces
     * it — two users typing PRD001 at the same moment both get "available"
     * here, and the second insert is the one that fails.
     */
    public function checkCode(Request $request): JsonResponse
    {
        $field = $request->string('field')->toString();

        if (! in_array($field, ['item_group_code', 'name'], true)) {
            return response()->json(['message' => 'Unknown field.'], 422);
        }

        $taken = Product::query()
            ->where($field, $request->string('value')->toString())
            ->when($request->filled('ignore'), fn ($q) => $q->whereKeyNot($request->integer('ignore')))
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    /**
     * Dropdown sources shared by the create and edit forms.
     *
     * Only active rows: a retired unit or GST rate must not be selectable on a
     * new product, while products already pointing at it keep rendering.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories'        => Category::active()->orderBy('name')->pluck('name', 'id'),

            // Unit is typed, not picked. These are only autocomplete hints from
            // what has already been entered, so "PCS" does not become "Pcs" and
            // "pcs" across three products and then three export documents.
            'unitSuggestions'   => $this->unitSuggestions(),

            'priceBands'        => PriceBand::active()->orderBy('code')->get()->pluck('label', 'id'),
            'gstRates'          => GstRate::active()->orderBy('rate')->get()->pluck('label', 'id'),
            'calculationBases'  => CalculationBasis::active()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * Distinct units already typed on other products, for the datalist.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function unitSuggestions(): \Illuminate\Support\Collection
    {
        return Product::query()->whereNotNull('unit_po')->distinct()->pluck('unit_po')
            ->merge(Product::query()->whereNotNull('unit_export')->distinct()->pluck('unit_export'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
