<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\DefectCode;
use App\Models\ProductionQcCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionCapaController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'open';

        $checks = ProductionQcCheck::query()
            ->with([
                'productionOrder:id,order_number,garment_style_id',
                'productionOrder.garmentStyle:id,style_number,name',
                'defectCode',
                'creator:id,name',
                'capaCloser:id,name',
            ])
            ->whereNotNull('capa_status')
            ->when($status !== 'all', fn ($q) => $q->where('capa_status', $status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('capa_plan', 'like', $term)
                        ->orWhereHas('productionOrder', fn ($o) => $o->where('order_number', 'like', $term))
                        ->orWhereHas('defectCode', fn ($d) => $d->where('code', 'like', $term)->orWhere('name', 'like', $term));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('manufacturing.capa.index', [
            'checks' => $checks,
            'filters' => [
                'status' => $status,
                'search' => $request->string('search')->toString(),
            ],
            'openCount' => ProductionQcCheck::query()->where('capa_status', 'open')->count(),
            'defectCodes' => DefectCode::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function close(Request $request, ProductionQcCheck $qcCheck): RedirectResponse
    {
        if ($qcCheck->capa_status !== 'open') {
            return back()->with('warning', 'This CAPA is not open.');
        }

        $data = $request->validate([
            'close_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $note = trim((string) ($data['close_note'] ?? ''));
        $plan = (string) $qcCheck->capa_plan;
        if ($note !== '') {
            $plan = trim($plan."\n\nClosed note: ".$note);
        }

        $qcCheck->update([
            'capa_status'    => 'closed',
            'capa_closed_at' => now(),
            'capa_closed_by' => $request->user()->id,
            'capa_plan'      => $plan,
        ]);

        return back()->with('success', 'CAPA closed for '.$qcCheck->productionOrder?->order_number.'.');
    }
}
