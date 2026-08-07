<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One column of the item table an order format draws.
 *
 * The prototype's Live Table Preview is the specification: "Exact table
 * structure as it appears in Inquiry -> OC -> PO".
 */
class DocumentFormatColumn extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'label',
        'is_enabled',
        'is_mandatory',
        'is_custom',
        'print_only',
        'sub_columns',
        'sort_order',
    ];

    /**
     * The columns every format starts with, in the order the prototype's
     * preview draws them, each with its default label and whether it is
     * print-only.
     *
     * `sr_no`, `qty` and `amount` are absent from the toggleable set on
     * purpose — a row with no serial number, no quantity and no total is not
     * an order line, so those are drawn unconditionally by whatever renders
     * the table rather than being switchable here.
     *
     * @var array<string, array{label: string, print_only: bool}>
     */
    public const STANDARD = [
        'supplier'    => ['label' => 'Supplier',          'print_only' => false],
        'design_no'   => ['label' => 'Design No. / Name', 'print_only' => false],
        'product'     => ['label' => 'Product Name',      'print_only' => false],
        'colour'      => ['label' => 'Colour',            'print_only' => false],
        'size'        => ['label' => 'Size',              'print_only' => false],
        'unit'        => ['label' => 'Unit',              'print_only' => false],
        'price'       => ['label' => 'Price',             'print_only' => false],
        // "image column hidden on screen · visible on print/PDF only"
        'image'       => ['label' => 'Image',             'print_only' => true],
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'   => 'boolean',
            'is_mandatory' => 'boolean',
            'is_custom'    => 'boolean',
            'print_only'   => 'boolean',
            'sub_columns'  => 'array',
            'sort_order'   => 'integer',
        ];
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(DocumentFormat::class, 'document_format_id');
    }

    /**
     * The default label for a standard key, or the key itself for a custom
     * column that has somehow lost its label.
     */
    public static function defaultLabel(string $key): string
    {
        return self::STANDARD[$key]['label'] ?? str($key)->headline()->toString();
    }
}
