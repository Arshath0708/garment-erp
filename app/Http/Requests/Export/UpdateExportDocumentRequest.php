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
        ];
    }
}
