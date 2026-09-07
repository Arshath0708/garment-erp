<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <div class="row g-3">
        <div class="col-md-3">
            <x-ui.card title="Purchase Orders">
                <div class="fs-4 fw-semibold">{{ number_format($stats['purchase_orders']) }}</div>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card title="Export Documents">
                <div class="fs-4 fw-semibold">{{ number_format($stats['export_documents']) }}</div>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card title="Open Shipments">
                <div class="fs-4 fw-semibold">{{ number_format($stats['open_shipments']) }}</div>
            </x-ui.card>
        </div>
        <div class="col-md-3">
            <x-ui.card title="Closed Shipments">
                <div class="fs-4 fw-semibold">{{ number_format($stats['closed_shipments']) }}</div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-8">
            <x-ui.card title="Power BI & CSV Report Packs" variant="primary">
                <p class="text-body-secondary small mb-3">
                    Download standardized CSV data packs formatted for direct import into Microsoft Power BI Desktop or Excel analysis.
                </p>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i> Open Purchase Orders CSV</div>
                            <div class="text-body-secondary small">Active fabric & trim POs with supplier details, delivery dates, and total values.</div>
                        </div>
                        <a href="{{ route('reports.open-pos.csv') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                            <i class="bi bi-download me-1"></i> Download CSV
                        </a>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-box-seam me-2 text-success"></i> Open Exports & Shipments CSV</div>
                            <div class="text-body-secondary small">Active buyer export documents, invoice numbers, shipment dates, and FOB values.</div>
                        </div>
                        <a href="{{ route('reports.open-exports.csv') }}" class="btn btn-sm btn-outline-success fw-semibold">
                            <i class="bi bi-download me-1"></i> Download CSV
                        </a>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-exclamation-triangle me-2 text-warning"></i> Open CAPA & Quality Holds CSV</div>
                            <div class="text-body-secondary small">Production orders with QC rejections, damaged piece counts, and manufacturing stage holds.</div>
                        </div>
                        <a href="{{ route('reports.open-capa.csv') }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                            <i class="bi bi-download me-1"></i> Download CSV
                        </a>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-md-4">
            <x-ui.card title="Report Navigation & Help">
                <div class="mb-3">
                    <div class="fw-semibold mb-1">Standard Reports</div>
                    <ul class="mb-0 small ps-3">
                        <li><a href="{{ route('reports.factory-board') }}">Factory board</a> — style, work order, cutting, stitching, packing, dispatch</li>
                        <li><a href="{{ route('reports.outstanding.index') }}">Supplier & Buyer Outstanding Ledger</a></li>
                        <li><a href="{{ route('reports.order-profit') }}">Profit per order (plan vs actual)</a></li>
                    </ul>
                </div>
                <hr>
                <div>
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1 text-info"></i> How to use CSV Packs</div>
                    <p class="text-body-secondary small mb-2">Instructions for loading CSV packs into Excel or Microsoft Power BI Desktop.</p>
                    <button class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#powerBiGuideModal">
                        <i class="bi bi-book me-1"></i> Read Power BI & Excel Guide
                    </button>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Power BI & Excel User Guide Modal -->
    <div class="modal fade" id="powerBiGuideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bar-chart-line me-2"></i> Power BI & Excel CSV Integration Guide</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-primary"><i class="bi bi-filetype-xlsx me-1"></i> 1. Opening CSV in Microsoft Excel</h6>
                    <ol class="small text-body-secondary mb-4">
                        <li>Click <strong>Download CSV</strong> on any report pack above.</li>
                        <li>Double-click the downloaded <code>.csv</code> file or open it inside Microsoft Excel.</li>
                        <li>The file includes a UTF-8 encoding header (BOM) so characters, numbers, and dates (<code>YYYY-MM-DD</code>) format automatically.</li>
                        <li>To view updated data, simply download a fresh CSV from ERP anytime.</li>
                    </ol>

                    <h6 class="fw-bold text-success"><i class="bi bi-graph-up me-1"></i> 2. Importing & Refreshing in Microsoft Power BI Desktop</h6>
                    <ol class="small text-body-secondary mb-3">
                        <li>Launch <strong>Power BI Desktop</strong>.</li>
                        <li>Click <strong>Get Data &rarr; Text/CSV</strong> and select your downloaded CSV file (e.g. <code>open-purchase-orders-2026-09-07.csv</code>).</li>
                        <li>Review the data preview window and click <strong>Load</strong> to generate visuals.</li>
                        <li><strong>Data Refresh Workflow:</strong> When you download a newer CSV report pack from ERP, save it to the same folder on your computer. In Power BI Desktop, click <strong>Refresh</strong> on the Home ribbon to update all charts instantly without rebuilding your reports.</li>
                    </ol>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="bi bi-lightbulb me-1"></i> <strong>Pro Tip:</strong> Keep the saved CSV file name consistent (e.g., <code>open-purchase-orders.csv</code>) so Power BI refreshes seamlessly without re-prompting for file paths.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Guide</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

