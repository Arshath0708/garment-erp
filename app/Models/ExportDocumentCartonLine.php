<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line inside one carton — a free-text description (what the packer
 * actually wrote on the carton) plus qty and unit, not a foreign key to
 * ExportDocumentItem. See the create-table migration's docblock.
 */
class ExportDocumentCartonLine extends Model
{
    protected $fillable = ['description', 'unit', 'qty', 'sort_order'];

    protected function casts(): array
    {
        return [
            'qty'        => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function carton(): BelongsTo
    {
        return $this->belongsTo(ExportDocumentCarton::class, 'export_document_carton_id');
    }
}
