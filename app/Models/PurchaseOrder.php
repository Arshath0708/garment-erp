<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase Order — one supplier's slice of a confirmed OC's items, raised via
 * OrderConfirmationService::raisePurchaseOrders(). `po_num` is deliberately
 * absent from $fillable, service-assigned like Inquiry::$inquiry_no.
 */
class PurchaseOrder extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * 'partial' and 'received' are reachable only once a Goods Inward module
     * exists to set them — see the 040000 migration's own comment. Declared
     * here so the value is valid the day that module reads it, not because
     * this screen can reach them.
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        'draft'    => 'Draft',
        'raised'   => 'Raised',
        'partial'  => 'Partial',
        'received' => 'Fully Received',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'draft'    => 'secondary',
        'raised'   => 'primary',
        'partial'  => 'warning',
        'received' => 'success',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_confirmation_id',
        'supplier_id',
        'po_date',
        'dispatch_date',
        'delivery_details',
        'packing_details',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'po_date'       => 'date',
            'dispatch_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(PurchaseOrderTimelineEntry::class)->orderBy('sort_order');
    }

    /** The OC items this PO was raised from — set on those items, not here. */
    public function raisedItems(): HasMany
    {
        return $this->hasMany(OrderConfirmationItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Derived
    |--------------------------------------------------------------------------
    */

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function totalAmount(): float
    {
        return (float) $this->items->sum('amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    public function searchable(): array
    {
        return ['po_num', 'remarks'];
    }

    public function sortable(): array
    {
        return ['id', 'po_num', 'po_date', 'status', 'created_at'];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            foreach ($this->searchable() as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }

            $q->orWhereHas('supplier', fn (Builder $s) => $s
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('display_code', 'like', "%{$term}%"));

            $q->orWhereHas('orderConfirmation', fn (Builder $o) => $o
                ->where('oc_num', 'like', "%{$term}%"));
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (blank($status) || ! array_key_exists($status, self::STATUSES)) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
