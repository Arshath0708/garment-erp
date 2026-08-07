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
 * Order Confirmation — the buyer's agreement to an inquiry's costed items, or
 * a direct contract raised without one. Confirmed lines here are what get
 * raised onto a Purchase Order, one PO per supplier.
 *
 * `oc_num` is deliberately absent from $fillable — service-assigned, same
 * treatment as Inquiry::$inquiry_no.
 */
class OrderConfirmation extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        'draft'     => 'Draft',
        'sent'      => 'OC Sent',
        'confirmed' => 'Confirmed',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'draft'     => 'secondary',
        'sent'      => 'info',
        'confirmed' => 'success',
    ];

    /**
     * 'direct' skips the OC document entirely: no items at this stage, the
     * contract number is only an anchor POs are raised against later.
     *
     * @var array<string, string>
     */
    public const MODES = [
        'oc'     => 'Order Confirmation',
        'direct' => 'Direct Buyer Contract',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mode',
        'oc_date',
        'buyer_ref',
        'source_inquiry_id',
        'buyer_id',
        'category_id',
        'document_format_id',
        'agent_id',
        'agent_commission_type',
        'agent_commission_value',
        'currency_id',
        'incoterm',
        'ship_method',
        'shipment_date',
        'pol',
        'pod',
        'payment_terms',
        'delivery_details',
        'packing_details',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'oc_date'                => 'date',
            'agent_commission_value' => 'decimal:4',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sourceInquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'source_inquiry_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(DocumentFormat::class, 'document_format_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderConfirmationItem::class)->orderBy('sort_order');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
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

    public function isDirect(): bool
    {
        return $this->mode === 'direct';
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
        return ['oc_num', 'buyer_ref', 'remarks'];
    }

    public function sortable(): array
    {
        return ['id', 'oc_num', 'oc_date', 'status', 'created_at'];
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

            $q->orWhereHas('buyer', fn (Builder $b) => $b
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('display_code', 'like', "%{$term}%"));
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
