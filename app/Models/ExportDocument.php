<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Export Document — one outward consignment to one buyer, raised from a
 * confirmed Order Confirmation. This is the "document set" the sidebar's
 * Export section refers to: booking, container and BL details live here,
 * and `checklist` is the live version of the 26-row sheet this module was
 * built from.
 *
 * `doc_num` is service-assigned, absent from $fillable — same treatment as
 * OrderConfirmation::$oc_num.
 */
class ExportDocument extends Model
{
    use Filterable, HasAuditColumns, SoftDeletes;

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        'draft'       => 'Draft',
        'in_progress' => 'In Progress',
        'closed'      => 'Closed',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'draft'       => 'secondary',
        'in_progress' => 'primary',
        'closed'      => 'success',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_confirmation_id',
        'buyer_id',
        'currency_id',
        'incoterm_id',
        'port_of_loading_id',
        'port_of_discharge_id',
        'shipment_method_id',
        'shipment_date',
        'remarks',
        'status',

        'invoice_no',
        'invoice_date',
        'exporter_ref',

        'forwarder_name',
        'forwarder_address',
        'vehicle_no',
        'driver_cell',

        'final_destination',
        'marks_and_numbers',
        'total_cartons',
        'package_kind',

        'freight_amount',
        'insurance_amount',
        'gross_weight',
    ];

    protected function casts(): array
    {
        return [
            'shipment_date'     => 'date',
            'invoice_date'      => 'date',
            'total_cartons'     => 'integer',
            'freight_amount'    => 'decimal:2',
            'insurance_amount'  => 'decimal:2',
            'gross_weight'      => 'decimal:3',
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

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function incoterm(): BelongsTo
    {
        return $this->belongsTo(Incoterm::class);
    }

    public function portOfLoading(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_loading_id');
    }

    public function portOfDischarge(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_discharge_id');
    }

    public function shipmentMethod(): BelongsTo
    {
        return $this->belongsTo(ShipmentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExportDocumentItem::class)->orderBy('sort_order');
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(ExportDocumentChecklist::class);
    }

    public function containers(): BelongsToMany
    {
        return $this->belongsToMany(Container::class);
    }

    /** The OC items this Export Document was raised from — set on those items, not here. */
    public function shippedItems(): HasMany
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

    /** "14 / 26" — how many checklist rows are past pending. */
    public function checklistProgress(): string
    {
        $entries = $this->checklist;

        return $entries->where('status', '!=', 'pending')->count().' / '.$entries->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering
    |--------------------------------------------------------------------------
    */

    public function searchable(): array
    {
        return ['doc_num', 'remarks'];
    }

    public function sortable(): array
    {
        return ['id', 'doc_num', 'shipment_date', 'status', 'created_at'];
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
