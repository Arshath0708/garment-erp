<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $items = Product::query()
            ->with('category:id,name')
            ->when($request->filled('kind'), fn ($q) => $q->where('item_kind', $request->string('kind')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('item_group_code', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.index', [
            'items' => $items,
            'kinds' => Product::KINDS,
            'filters' => $request->only('search', 'kind'),
        ]);
    }
}
