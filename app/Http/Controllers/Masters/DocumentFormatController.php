<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreDocumentFormatRequest;
use App\Http\Requests\Masters\UpdateDocumentFormatRequest;
use App\Models\DocumentFormat;
use App\Services\Masters\DocumentFormatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Order Format master.
 *
 * Route-model bound as {format} rather than {documentFormat}: the client calls
 * these order formats, and the URL is the one place that name is visible.
 */
class DocumentFormatController extends Controller implements HasMiddleware
{
    public function __construct(private readonly DocumentFormatService $formats)
    {
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
            new Middleware('permission:po-format.view', only: ['index', 'show']),
            new Middleware('permission:po-format.create', only: ['create', 'store']),
            new Middleware('permission:po-format.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:po-format.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $formats = DocumentFormat::query()
            ->withCount(['categories', 'units', 'images'])
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(10)
            ->withQueryString();

        return view('masters.formats.index', [
            'formats' => $formats,
            'filters' => $request->only('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function create(): View
    {
        $defaults = $this->formats->defaults();

        return view('masters.formats.create', [
            'format'  => null,
            'columns' => $defaults['columns'],
            'units'   => $defaults['units'],
        ]);
    }

    public function store(StoreDocumentFormatRequest $request): RedirectResponse
    {
        $format = $this->formats->create($request->validated());

        return redirect()
            ->route('masters.formats.index')
            ->with('success', "Order format \"{$format->name}\" created successfully.");
    }

    public function show(DocumentFormat $format): View
    {
        return view('masters.formats.show', [
            'format' => $format->load(['units', 'columns', 'images', 'categories', 'creator', 'updater']),
        ]);
    }

    public function edit(DocumentFormat $format): View
    {
        $format->load(['units', 'columns', 'images']);

        return view('masters.formats.edit', [
            'format'  => $format,
            'columns' => $this->columnState($format),
            'units'   => $format->units->pluck('name')->all(),
        ]);
    }

    public function update(UpdateDocumentFormatRequest $request, DocumentFormat $format): RedirectResponse
    {
        $this->formats->update($format, $request->validated());

        return redirect()
            ->route('masters.formats.index')
            ->with('success', "Order format \"{$format->name}\" updated successfully.");
    }

    public function destroy(DocumentFormat $format): RedirectResponse
    {
        $check = $this->formats->canDelete($format);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $name = $format->name;
        $this->formats->delete($format);

        return redirect()
            ->route('masters.formats.index')
            ->with('success', "Order format \"{$name}\" deleted successfully.");
    }

    public function toggleStatus(DocumentFormat $format): RedirectResponse
    {
        $format->update([
            'status' => $format->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "Order format \"{$format->name}\" marked {$format->status}.");
    }

    /**
     * The saved format's columns in the shape the form expects.
     *
     * Falls back to the defaults for any standard key the row set is missing,
     * so a format saved before a new standard column was introduced still
     * renders a complete grid rather than a gap.
     *
     * @return array<string, array{label: string, enabled: bool, mandatory: bool, print_only: bool, sub_columns: array}>
     */
    private function columnState(DocumentFormat $format): array
    {
        $state = $this->formats->defaults()['columns'];

        foreach ($format->columns->where('is_custom', false) as $column) {
            if (! array_key_exists($column->key, $state)) {
                continue;
            }

            $state[$column->key] = [
                'label'       => $column->label,
                'enabled'     => $column->is_enabled,
                'mandatory'   => $column->is_mandatory,
                'print_only'  => $column->print_only,
                'sub_columns' => $column->sub_columns ?? [],
            ];
        }

        return $state;
    }
}
