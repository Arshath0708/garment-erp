<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLot;
use App\Models\StyleStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $items = Product::query()
            ->with('category:id,name')
            ->withSum('stockLots as lots_qty', 'qty_on_hand')
            ->withCount('stockLots')
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

        $finishedGoods = StyleStock::query()
            ->with('garmentStyle')
            ->where('qty_on_hand', '>', 0)
            ->orderByDesc('qty_on_hand')
            ->get();

        $lowStock = Product::query()
            ->where('reorder_level', '>', 0)
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->orderBy('name')
            ->get();

        return view('inventory.index', [
            'items' => $items,
            'finishedGoods' => $finishedGoods,
            'lowStock' => $lowStock,
            'kinds' => Product::KINDS,
            'filters' => $request->only('search', 'kind'),
            'warehouseCount' => Warehouse::query()->where('is_active', true)->count(),
        ]);
    }

    public function lots(Request $request): View
    {
        $lots = StockLot::query()
            ->with(['product:id,name,item_group_code,unit_po', 'warehouse:id,code,name'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('lot_no', 'like', $term)
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('item_group_code', 'like', $term));
                });
            })
            ->where('qty_on_hand', '>', 0)
            ->orderByDesc('received_at')
            ->paginate(30)
            ->withQueryString();

        return view('inventory.lots', [
            'lots' => $lots,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only('search', 'warehouse_id', 'product_id'),
        ]);
    }
}
