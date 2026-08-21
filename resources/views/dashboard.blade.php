<x-app-layout>
    <x-slot name="header">Garment Manufacturing Overview</x-slot>

    <!-- SECTION 1: TODAY'S PRODUCTION -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-activity text-primary me-2"></i> Today's Production</h4>
                <div class="fs-5 text-primary fw-bold">PO-00452 — 10,000 pcs</div>
            </div>
            <a href="{{ route('manufacturing.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-gear-wide-connected me-1"></i> Open Manufacturing Desk
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Cutting 85% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-scissors text-primary me-1"></i> Cutting</span>
                            <span class="badge bg-primary fs-6">85%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 85%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 8,500 pcs</span>
                            <span>Pending: 1,500 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Printing 82% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-printer text-info me-1"></i> Printing</span>
                            <span class="badge bg-info text-dark fs-6">82%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 82%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 8,200 pcs</span>
                            <span>Pending: 1,800 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Stitching 65% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-gear-wide-connected text-secondary me-1"></i> Stitching</span>
                            <span class="badge bg-secondary fs-6">65%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 65%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 6,500 pcs</span>
                            <span>Pending: 3,500 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Finishing 58% (Attention Required) -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-warning-emphasis"><i class="bi bi-shield-exclamation text-warning me-1"></i> 🟠 Finishing</span>
                            <span class="badge bg-warning text-dark fs-6">58%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 58%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 5,800 pcs</span>
                            <span>Pending: 4,200 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Packing 55% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-box-seam text-success me-1"></i> Packing</span>
                            <span class="badge bg-success fs-6">55%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 55%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 5,500 pcs</span>
                            <span>Pending: 4,500 pcs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: IMPORTANT ALERTS -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0 text-danger"><i class="bi bi-bell-fill me-2"></i> Important Alerts</h4>
            <span class="badge bg-danger">Exception Monitor</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Alert 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">1,500 pcs cutting pending</div>
                            <span class="text-body-secondary small">PO-00452 cutting delay</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">2,000 pcs stitching pending</div>
                            <span class="text-body-secondary small">Jobber Line 3 delay</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Fabric shortage for PO-00453</div>
                            <span class="text-body-secondary small">Woven Twill stock low</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Jobber material pending</div>
                            <span class="text-body-secondary small">Outsourced printing unit</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-x-circle-fill text-danger fs-3"></i>
                        <div>
                            <div class="fw-bold text-danger">Invoice quantity mismatch</div>
                            <span class="text-body-secondary small">OCR document checker alert</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Shipment due in 3 days</div>
                            <span class="text-body-secondary small">Container loading scheduled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
