<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\GarmentStyle;
use App\Services\Inventory\MaterialPlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BOMController extends Controller
{
    public function index(Request $request, MaterialPlanService $plan): View
    {
        $styles = GarmentStyle::query()->with(['buyer', 'materials.product', 'costings'])->orderByDesc('id')->paginate(15);
        $selected = $styles->firstWhere('id', $request->integer('style_id')) ?? $styles->first();
        $orderQty = max(1, $request->integer('qty', (int) ($selected?->target_qty ?: 1)));
        $planRows = $selected ? $plan->preview($selected, $orderQty) : [];

        return view('masters.bom.index', compact('styles', 'selected', 'orderQty', 'planRows'));
    }
}
