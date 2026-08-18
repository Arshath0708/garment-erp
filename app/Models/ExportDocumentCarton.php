<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One physical carton on a shipment — feeds Packing List Formats B and C,
 * which print carton by carton rather than the flat item list Format A uses.
 */
class ExportDocumentCarton extends Model
{
    protected $fillable = ['carton_no', 'net_weight', 'gross_weight', 'dimensions', 'sort_order'];

    protected function casts(): array
    {
        return [
            'net_weight'   => 'decimal:3',
            'gross_weight' => 'decimal:3',
            'sort_order'   => 'integer',
        ];
    }

    public function exportDocument(): BelongsTo
    {
        return $this->belongsTo(ExportDocument::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExportDocumentCartonLine::class, 'export_document_carton_id')->orderBy('sort_order');
    }

    public function totalQty(): int
    {
        return (int) $this->lines->sum('qty');
    }
}
