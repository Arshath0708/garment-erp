<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\GarmentStyle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BOMController extends Controller
{
    public function index(Request $request): View
    {
        $styles = GarmentStyle::query()->with('buyer')->orderByDesc('id')->paginate(15);

        return view('masters.bom.index', compact('styles'));
    }
}
