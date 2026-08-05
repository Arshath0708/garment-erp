<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreFobValueRequest;
use App\Http\Requests\Masters\UpdateFobValueRequest;
use App\Models\FobValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class FobValueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:fob-value.view', only: ['index', 'show']),
            new Middleware('permission:fob-value.create', only: ['create', 'store']),
            new Middleware('permission:fob-value.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:fob-value.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $fobValues = FobValue::query()
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('masters.fob-values.index', [
            'fobValues' => $fobValues,
            'filters'   => $request->only('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        return view('masters.fob-values.create');
    }

    public function store(StoreFobValueRequest $request): RedirectResponse
    {
        $fobValue = FobValue::create($request->validated());

        return redirect()
            ->route('masters.fob-values.index')
            ->with('success', "FOB Value \"{$fobValue->name}\" created successfully.");
    }

    public function show(FobValue $fobValue): View
    {
        return view('masters.fob-values.show', [
            'fobValue' => $fobValue->load('creator', 'updater'),
        ]);
    }

    public function edit(FobValue $fobValue): View
    {
        return view('masters.fob-values.edit', [
            'fobValue' => $fobValue,
        ]);
    }

    public function update(UpdateFobValueRequest $request, FobValue $fobValue): RedirectResponse
    {
        $fobValue->update($request->validated());

        return redirect()
            ->route('masters.fob-values.index')
            ->with('success', "FOB Value \"{$fobValue->name}\" updated successfully.");
    }

    public function destroy(FobValue $fobValue): RedirectResponse
    {
        $fobValue->delete();

        return redirect()
            ->route('masters.fob-values.index')
            ->with('success', "FOB Value \"{$fobValue->name}\" deleted successfully.");
    }

    public function toggleStatus(FobValue $fobValue): RedirectResponse
    {
        $fobValue->update([
            'status' => $fobValue->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "FOB Value \"{$fobValue->name}\" marked {$fobValue->status}.");
    }
}
