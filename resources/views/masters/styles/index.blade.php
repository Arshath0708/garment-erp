<x-app-layout>
    <x-slot name="header">Style Master & Tech Packs</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Manage garment styles, colorways, fabric compositions, and technical specifications.</p>
        </div>
        <a href="{{ route('masters.styles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create New Style
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('masters.styles.index') }}" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search text-body-secondary"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by style #, name, fabric, or color..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary w-100">Filter Styles</button>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-body-secondary small">Total Styles: <strong>{{ $styles->total() }}</strong></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Styles Data Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Style #</th>
                        <th>Style Name</th>
                        <th>Buyer</th>
                        <th>Category</th>
                        <th>Season</th>
                        <th>Fabric / Composition</th>
                        <th>Colorway</th>
                        <th>Target Qty</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($styles as $style)
                        <tr>
                            <td class="fw-bold">
                                <a href="{{ route('masters.styles.show', $style) }}" class="text-primary text-decoration-none">
                                    {{ $style->style_number }}
                                </a>
                            </td>
                            <td>{{ $style->name }}</td>
                            <td>{{ $style->buyer ? $style->buyer->company_name : '—' }}</td>
                            <td>
                                @if ($style->category)
                                    <span class="badge bg-info text-dark">{{ $style->category->name }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $style->season ?: '—' }}</td>
                            <td>{{ $style->fabric ?: '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $style->color ?: 'Standard' }}</span></td>
                            <td class="fw-bold">{{ number_format($style->target_qty) }} pcs</td>
                            <td>
                                <span class="badge bg-success">{{ $style->status }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('masters.styles.show', $style) }}" class="btn btn-outline-primary" title="View Tech Pack">
                                        <i class="bi bi-file-earmark-text me-1"></i> Tech Pack
                                    </a>
                                    <a href="{{ route('masters.styles.edit', $style) }}" class="btn btn-outline-secondary" title="Edit Style">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('masters.styles.destroy', $style) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this style?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Style">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-body-secondary">
                                No garment styles found. Click <strong>Create New Style</strong> to add your first style.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body border-0 py-3">
            {{ $styles->links() }}
        </div>
    </div>
</x-app-layout>
