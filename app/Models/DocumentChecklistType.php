<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row of the "Export docs to payment receipt from buyer" checklist —
 * Packing List, Bill of Lading, eBRC, and so on. Seeded once by
 * DocumentChecklistTypeSeeder; ExportDocumentChecklist is the per-shipment
 * status against each one.
 */
class DocumentChecklistType extends Model
{
    use Filterable;

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'generated' => 'Generated',
        'uploaded'  => 'Uploaded',
        'manual'    => 'Manual',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'variant_labels',
        'closes_shipment',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'variant_labels'  => 'array',
            'closes_shipment' => 'boolean',
            'sort_order'      => 'integer',
        ];
    }

    public function checklistEntries(): HasMany
    {
        return $this->hasMany(ExportDocumentChecklist::class);
    }

    public function hasVariants(): bool
    {
        return filled($this->variant_labels);
    }

    /** Whether the UI should show a file-upload control for this type. */
    public function requiresFile(): bool
    {
        return $this->category !== 'manual';
    }

    public function searchable(): array
    {
        return ['code', 'name'];
    }

    public function sortable(): array
    {
        return ['id', 'name', 'sort_order', 'status'];
    }
}
