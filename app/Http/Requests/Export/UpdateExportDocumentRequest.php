<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The shipment-logistics fields the Delivery Challan / Export Invoice /
 * E-way Bill paperwork needs — none of it is derivable from the OC, so it's
 * all manual entry on the Export Document's own edit screen.
 */
class UpdateExportDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('export-document.edit');
    }

    public function rules(): array
    {
        return [
            'incoterm_id'          => ['nullable', 'integer', Rule::exists('incoterms', 'id')],
            'port_of_loading_id'   => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'port_of_discharge_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'shipment_method_id'   => ['nullable', 'integer', Rule::exists('shipment_methods', 'id')],
            'shipment_date'        => ['nullable', 'date'],
            'remarks'              => ['nullable', 'string', 'max:2000'],

            'invoice_no'    => ['nullable', 'string', 'max:60'],
            'invoice_date'  => ['nullable', 'date'],
            'exporter_ref'  => ['nullable', 'string', 'max:255'],

            // Packing List Format C's own header fields.
            'buyer_ref_no'      => ['nullable', 'string', 'max:60'],
            'buyer_ref_date'    => ['nullable', 'date'],
            'other_reference'   => ['nullable', 'string', 'max:255'],
            'consignee_name'    => ['nullable', 'string', 'max:200'],
            'consignee_address' => ['nullable', 'string', 'max:1000'],
            'pre_carriage_by'   => ['nullable', 'string', 'max:60'],
            'place_of_receipt'  => ['nullable', 'string', 'max:150'],
            'vessel_flight_no'  => ['nullable', 'string', 'max:60'],
            'country_of_origin' => ['nullable', 'string', 'max:60'],

            'forwarder_name'    => ['nullable', 'string', 'max:150'],
            'forwarder_address' => ['nullable', 'string', 'max:1000'],
            'vehicle_no'        => ['nullable', 'string', 'max:60'],
            'driver_cell'       => ['nullable', 'string', 'max:30'],

            'final_destination' => ['nullable', 'string', 'max:150'],
            'marks_and_numbers' => ['nullable', 'string', 'max:2000'],
            'total_cartons'     => ['nullable', 'integer', 'min:0', 'max:999999'],
            'package_kind'      => ['nullable', 'string', 'max:40'],

            'freight_amount'   => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'insurance_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'gross_weight'     => ['nullable', 'numeric', 'min:0', 'max:9999999999.999'],
            'net_weight'       => ['nullable', 'numeric', 'min:0', 'max:9999999999.999'],
            'carton_dimensions' => ['nullable', 'string', 'max:60'],

            // Bill of Lading (Draft).
            'booking_no'          => ['nullable', 'string', 'max:60'],
            'bl_no'               => ['nullable', 'string', 'max:60'],
            'voyage_no'           => ['nullable', 'string', 'max:60'],
            'transshipment_port'  => ['nullable', 'string', 'max:150'],
            'notify_party_name'   => ['nullable', 'string', 'max:200'],
            'notify_party_address' => ['nullable', 'string', 'max:1000'],
            'goods_description'   => ['nullable', 'string', 'max:1000'],
            'total_measurement'   => ['nullable', 'numeric', 'min:0', 'max:9999999999.999'],
            'ex_rate'             => ['nullable', 'string', 'max:60'],
            'freight_terms'       => ['nullable', Rule::in(['PREPAID', 'COLLECT'])],
            'freight_prepaid_at'  => ['nullable', 'string', 'max:100'],
            'freight_payable_at'  => ['nullable', 'string', 'max:100'],
            'total_prepaid_in'    => ['nullable', 'string', 'max:150'],
            'no_of_original_bls'  => ['nullable', 'string', 'max:40'],
            'bl_place_of_issue'   => ['nullable', 'string', 'max:100'],
            'bl_date_of_issue'    => ['nullable', 'date'],

            // Packing List Formats B and C — the per-carton breakdown.
            'cartons'                        => ['nullable', 'array', 'max:200'],
            'cartons.*.carton_no'            => ['required_with:cartons.*.lines', 'nullable', 'string', 'max:40'],
            'cartons.*.net_weight'           => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
            'cartons.*.gross_weight'         => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
            'cartons.*.dimensions'           => ['nullable', 'string', 'max:60'],
            'cartons.*.lines'                => ['nullable', 'array', 'max:100'],
            'cartons.*.lines.*.description'  => ['required_with:cartons.*.lines.*.qty', 'nullable', 'string', 'max:500'],
            'cartons.*.lines.*.unit'         => ['nullable', 'string', 'max:20'],
            'cartons.*.lines.*.qty'          => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
