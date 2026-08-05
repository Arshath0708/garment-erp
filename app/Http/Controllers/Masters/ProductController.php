<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreProductRequest;
use App\Http\Requests\Masters\UpdateProductRequest;
use App\Models\CalculationBasis;
use App\Models\Category;
use App\Models\DocumentFormatUnit;
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
            ->paginate(10)
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
        return view('masters.products.edit', $this->formData($product) + [
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

        $taken = Product::withTrashed()
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
    private function formData(?Product $product = null): array
    {
        $categoriesQuery = Category::query();
        if ($product) {
            $categoriesQuery->where(function ($q) use ($product) {
                $q->active()->orWhere('id', $product->category_id);
            });
        } else {
            $categoriesQuery->active();
        }

        $priceBandsQuery = PriceBand::query();
        if ($product) {
            $priceBandsQuery->where(function ($q) use ($product) {
                $q->active()->orWhere('id', $product->price_band_id);
            });
        } else {
            $priceBandsQuery->active();
        }

        $gstRatesQuery = GstRate::query();
        if ($product) {
            $gstRatesQuery->where(function ($q) use ($product) {
                $q->active()->orWhere('id', $product->gst_rate_id);
            });
        } else {
            $gstRatesQuery->active();
        }

        return [
            'categories'        => $categoriesQuery->orderBy('name')->pluck('name', 'id'),

            // Picked, not typed — synced from the units defined on the Order
            // Format master, so "PCS" cannot drift into "Pcs" on one product
            // and "pcs" on the next.
            'units'             => $this->unitOptions($product),

            'priceBands'        => $priceBandsQuery->orderBy('code')->get()->pluck('label', 'id'),
            'gstRates'          => $gstRatesQuery->orderBy('rate')->get()->pluck('label', 'id'),
            'calculationBases'  => CalculationBasis::active()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * The dropdown source for Unit (PO & OC) / Unit (Export Docs) — every
     * distinct unit defined across all Order Formats, the same list the
     * Format master's "Units" section edits as chips.
     *
     * A product's own saved units are folded in too, even if no format
     * carries them any more — editing an older product must not blank out a
     * value nobody has touched.
     *
     * @return array<string, string>
     */
    private function unitOptions(?Product $product = null): array
    {
        $units = DocumentFormatUnit::query()->distinct()->pluck('name');

        if ($product) {
            $units = $units->push($product->unit_po, $product->unit_export);
        }

        return $units->filter()->unique()->sort()->values()
            ->mapWithKeys(fn ($unit) => [$unit => $unit])->all();
    }
}
