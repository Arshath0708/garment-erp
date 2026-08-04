<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Format master.
 *
 * A format decides the shape of the item table on every Inquiry, OC and PO
 * that uses it: which columns are drawn and what they are called, which units
 * a row may be priced in, whether one item row can carry several colours, and
 * the delivery and packing text that prints beneath the table.
 *
 * Categories link to formats, not the other way round — see the 000300
 * category_format migration. A format offered under a category is a choice the
 * order screen makes, not one the category makes for it.
 */
class DocumentFormat extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'module',
        'blade_view',
        'status',
        'allow_multiple_colours',
        'delivery_details',
        'packing_details',
    ];

    protected function casts(): array
    {
        return ['allow_multiple_colours' => 'boolean'];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function units(): HasMany
    {
        return $this->hasMany(DocumentFormatUnit::class)->orderBy('sort_order');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(DocumentFormatColumn::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(DocumentFormatImage::class)->orderBy('sort_order');
    }

    /** Category sheet col F, from the format's side. */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_format');
    }

    /*
    |--------------------------------------------------------------------------
    | Reading the format
    |--------------------------------------------------------------------------
    */

    /**
     * The columns that appear on a data-entry screen — everything enabled that
     * is not marked print-only.
     *
     * @return \Illuminate\Support\Collection<int, DocumentFormatColumn>
     */
    public function screenColumns()
    {
        return $this->columns->where('is_enabled', true)->where('print_only', false);
    }

    /**
     * Every column that appears on the printed document, print-only ones
     * included.
     *
     * @return \Illuminate\Support\Collection<int, DocumentFormatColumn>
     */
    public function printColumns()
    {
        return $this->columns->where('is_enabled', true);
    }

    /**
     * The Price column's header, with the unit appended.
     *
     * "The selected unit in a PO row will auto-populate the label in the Price
     * column header (e.g. Price / PCS)." Rendered here rather than in Blade so
     * the preview on the format screen and the real PO cannot drift apart.
     */
    public function priceLabel(?string $unit = null): string
    {
        $base = $this->columns->firstWhere('key', 'price')?->label ?? 'Price';
        $unit ??= $this->units->first()?->name;

        return $unit ? "{$base} / {$unit}" : $base;
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return array<int, string>
     */
    public function sortable(): array
    {
        return ['id', 'name', 'status', 'created_at'];
    }
}
