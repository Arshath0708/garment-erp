<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreCategoryRequest;
use App\Http\Requests\Masters\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\DocumentFormat;
use App\Services\Masters\CategoryService;
use App\Services\NumberSeriesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly NumberSeriesService $numbers,
    ) {
    }

    /**
     * Per-action permissions live here rather than on the route, so a new
     * action cannot be added without also deciding what guards it.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:category.view', only: ['index', 'show']),
            new Middleware('permission:category.create', only: ['create', 'store']),
            new Middleware('permission:category.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:category.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with('poFormat:id,name')
            ->withCount('products')
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.categories.index', [
            'categories' => $categories,
            'filters'    => $request->only('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        return view('masters.categories.create', [
            'poFormats' => $this->poFormats(),

            // Shown read-only so the user knows what they are about to get.
            // Not reserved — the real code is assigned at insert time.
            'nextCode'  => $this->numbers->preview('category'),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        return redirect()
            ->route('masters.categories.index')
            ->with('success', "Category {$category->code} created successfully.");
    }

    public function show(Category $category): View
    {
        return view('masters.categories.show', [
            'category' => $category->load('poFormat', 'creator', 'updater')->loadCount('products'),
        ]);
    }

    public function edit(Category $category): View
    {
        return view('masters.categories.edit', [
            'category'  => $category,
            'poFormats' => $this->poFormats(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('masters.categories.index')
            ->with('success', "Category {$category->code} updated successfully.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $check = $this->categories->canDelete($category);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $category->delete();

        return redirect()
            ->route('masters.categories.index')
            ->with('success', "Category {$category->code} deleted successfully.");
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Category {$category->code} marked {$category->status}.");
    }

    /**
     * Category sheet col F: "drop down with search bar to look for particular
     * formats". Inactive formats are excluded so a retired template cannot be
     * attached to a new category — existing links to it keep working.
     */
    private function poFormats(): \Illuminate\Support\Collection
    {
        return DocumentFormat::query()
            ->where('module', 'po')
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
