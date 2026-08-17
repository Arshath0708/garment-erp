<x-app-layout>
    <x-slot name="header">Edit Export Document {{ $document->doc_num }}</x-slot>

    <x-ui.card :title="'Edit '.$document->doc_num" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('export.documents.show', $document) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <p class="text-body-secondary small mb-4">
            Shipment logistics details — printed on the Delivery Challan, Export Invoice and E-way Bill. None of
            this comes from the Order Confirmation automatically; fill it in once the shipment is being packed.
        </p>

        <form action="{{ route('export.documents.update', $document) }}" method="POST">
            @csrf
            @method('PUT')

            <h6 class="fw-semibold mb-2">Shipment</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Incoterm</label>
                    <select name="incoterm_id" class="form-select @error('incoterm_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach($incoterms as $id => $code)
                            <option value="{{ $id }}" @selected((string) old('incoterm_id', $document->incoterm_id) === (string) $id)>{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('incoterm_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Shipment Method</label>
                    <select name="shipment_method_id" class="form-select @error('shipment_method_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach($shipmentMethods as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('shipment_method_id', $document->shipment_method_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('shipment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Port of Loading</label>
                    <select name="port_of_loading_id" class="form-select @error('port_of_loading_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach($ports as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('port_of_loading_id', $document->port_of_loading_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('port_of_loading_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Port of Discharge</label>
                    <select name="port_of_discharge_id" class="form-select @error('port_of_discharge_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach($ports as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('port_of_discharge_id', $document->port_of_discharge_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('port_of_discharge_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Final Destination</label>
                    <input type="text" name="final_destination" value="{{ old('final_destination', $document->final_destination) }}"
                           class="form-control @error('final_destination') is-invalid @enderror" placeholder="Defaults to Port of Discharge">
                    @error('final_destination') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Shipment Date</label>
                    <input type="date" name="shipment_date" value="{{ old('shipment_date', $document->shipment_date?->toDateString()) }}"
                           class="form-control @error('shipment_date') is-invalid @enderror">
                    @error('shipment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-semibold mb-2">Invoice Reference</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Invoice No.</label>
                    <input type="text" name="invoice_no" value="{{ old('invoice_no', $document->invoice_no) }}"
                           class="form-control @error('invoice_no') is-invalid @enderror" placeholder="e.g. EXP25269001">
                    @error('invoice_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', $document->invoice_date?->toDateString()) }}"
                           class="form-control @error('invoice_date') is-invalid @enderror">
                    @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exporter's Ref</label>
                    <input type="text" name="exporter_ref" value="{{ old('exporter_ref', $document->exporter_ref) }}"
                           class="form-control @error('exporter_ref') is-invalid @enderror">
                    @error('exporter_ref') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-semibold mb-2">Forwarder / Transport <span class="text-body-secondary fw-normal">(Delivery Challan)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Forwarder / CHA Name</label>
                    <input type="text" name="forwarder_name" value="{{ old('forwarder_name', $document->forwarder_name) }}"
                           class="form-control @error('forwarder_name') is-invalid @enderror">
                    @error('forwarder_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Forwarder Address</label>
                    <input type="text" name="forwarder_address" value="{{ old('forwarder_address', $document->forwarder_address) }}"
                           class="form-control @error('forwarder_address') is-invalid @enderror">
                    @error('forwarder_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle / Tempo No.</label>
                    <input type="text" name="vehicle_no" value="{{ old('vehicle_no', $document->vehicle_no) }}"
                           class="form-control @error('vehicle_no') is-invalid @enderror">
                    @error('vehicle_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Driver Cell No.</label>
                    <input type="text" name="driver_cell" value="{{ old('driver_cell', $document->driver_cell) }}"
                           class="form-control @error('driver_cell') is-invalid @enderror">
                    @error('driver_cell') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-semibold mb-2">Marks &amp; Packages <span class="text-body-secondary fw-normal">(Delivery Challan)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label">Marks &amp; Nos.</label>
                    <textarea name="marks_and_numbers" rows="4"
                              class="form-control @error('marks_and_numbers') is-invalid @enderror"
                              placeholder="Buyer name, destination, carton number ranges...">{{ old('marks_and_numbers', $document->marks_and_numbers) }}</textarea>
                    @error('marks_and_numbers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Cartons</label>
                    <input type="number" min="0" name="total_cartons" value="{{ old('total_cartons', $document->total_cartons) }}"
                           class="form-control @error('total_cartons') is-invalid @enderror">
                    @error('total_cartons') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Package Kind</label>
                    <input type="text" name="package_kind" value="{{ old('package_kind', $document->package_kind) }}"
                           class="form-control @error('package_kind') is-invalid @enderror">
                    @error('package_kind') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-semibold mb-2">CIF Value <span class="text-body-secondary fw-normal">(only if the incoterm needs it — manual entry, no auto-calculation yet)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Freight Amount</label>
                    <input type="number" step="0.01" min="0" name="freight_amount" value="{{ old('freight_amount', $document->freight_amount) }}"
                           class="form-control @error('freight_amount') is-invalid @enderror">
                    @error('freight_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Insurance Amount</label>
                    <input type="number" step="0.01" min="0" name="insurance_amount" value="{{ old('insurance_amount', $document->insurance_amount) }}"
                           class="form-control @error('insurance_amount') is-invalid @enderror">
                    @error('insurance_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gross Weight (kg)</label>
                    <input type="number" step="0.001" min="0" name="gross_weight" value="{{ old('gross_weight', $document->gross_weight) }}"
                           class="form-control @error('gross_weight') is-invalid @enderror">
                    @error('gross_weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="col-12 mb-4">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $document->remarks) }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save
                </button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
