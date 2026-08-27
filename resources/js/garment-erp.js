/* Garment ERP Interactive Controller */

document.addEventListener('alpine:init', () => {
    // 1. Manufacturing Workflow Interactive Engine
    Alpine.data('manufacturingWorkflow', () => ({
        activeStage: 'cutting',
        stages: {
            'buyer_po': {
                name: 'Buyer Purchase Order',
                icon: 'bi-file-earmark-spreadsheet',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: 'Verified & Approved',
                badgeClass: 'erp-badge-success',
                location: 'Bangalore HQ / Dubai Desk',
                targetDate: '2026-09-15',
                alerts: '100% Buyer PO deposit cleared.',
                details: 'Buyer PO raised with size breakups S:2000, M:4000, L:4500, XL:2000.'
            },
            'style_techpack': {
                name: 'Style & Tech Pack',
                icon: 'bi-scissors',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: 'Tech Pack Released v2.1',
                badgeClass: 'erp-badge-success',
                location: 'Bangalore R&D Studio',
                targetDate: '2026-08-20',
                alerts: 'Grading charts and measurement specs locked.',
                details: 'Spec Sheet, seam allowances, and embroidery placements finalized.'
            },
            'bom': {
                name: 'Bill of Materials (BOM)',
                icon: 'bi-list-check',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: 'BOM Costed & Locked',
                badgeClass: 'erp-badge-success',
                location: 'Bangalore Sourcing Unit',
                targetDate: '2026-08-22',
                alerts: 'Fabric, recycled polyester buttons, & care labels reserved.',
                details: 'Total BOM material requirement calculated with 3% wastage allowance.'
            },
            'costing': {
                name: 'Costing & Margin Analysis',
                icon: 'bi-calculator',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: 'Cost Approved ($ 11.45/pc)',
                badgeClass: 'erp-badge-success',
                location: 'Mumbai Finance Hub',
                targetDate: '2026-08-23',
                alerts: 'Profit margin targeted at 24.8%.',
                details: 'Includes direct fabric cost, trims, job-work washing, labor, freight & duty.'
            },
            'production_planning': {
                name: 'Production Planning (PPC)',
                icon: 'bi-calendar-event',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: 'Lines Scheduled (Line 3 & 4)',
                badgeClass: 'erp-badge-primary',
                location: 'Tirupur Plant 1',
                targetDate: '2026-08-25',
                alerts: 'Machine layout & SAM (24.5 mins) benchmarked.',
                details: 'Scheduled across 2 sewing lines with daily target of 650 pcs/line.'
            },
            'store_allocation': {
                name: 'Store & Material Allocation',
                icon: 'bi-box-seam',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 12500,
                pendingQty: 0,
                status: '100% Issued to Floor',
                badgeClass: 'erp-badge-success',
                location: 'Tirupur Main Store',
                targetDate: '2026-08-26',
                alerts: '18,750 meters of 100% Cotton Twill issued with shade grouping.',
                details: 'Roll-wise inspection completed (4-point system pass).'
            },
            'cutting': {
                name: 'Cutting Section',
                icon: 'bi-aspect-ratio',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 9800,
                pendingQty: 2700,
                status: 'In Progress (78.4%)',
                badgeClass: 'erp-badge-primary',
                location: 'Tirupur Plant 1 - Spreading & Cutting Bay',
                targetDate: '2026-08-29',
                alerts: 'Automated CAD Spreader operating at 98.2% nesting efficiency.',
                details: 'Bundles 001 through 392 numbered, stickered and routed to sewing lines.'
            },
            'stitching': {
                name: 'Stitching / Sewing Lines',
                icon: 'bi-cpu',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 6200,
                pendingQty: 6300,
                status: 'In Progress (49.6%)',
                badgeClass: 'erp-badge-primary',
                location: 'Tirupur Sewing Floor Lines 3 & 4',
                targetDate: '2026-09-04',
                alerts: 'Line 3 running ahead by +45 pcs/day. Bottleneck cleared at cuff attachment.',
                details: 'In-line DHU running low at 1.4%.'
            },
            'finishing': {
                name: 'Finishing & Thread Trimming',
                icon: 'bi-magic',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 4100,
                pendingQty: 8400,
                status: 'In Progress (32.8%)',
                badgeClass: 'erp-badge-warning',
                location: 'Tirupur Finishing Unit',
                targetDate: '2026-09-08',
                alerts: 'Steam ironing & garment buttoning station active.',
                details: 'Garments moving smoothly from Job-work washing department.'
            },
            'qc': {
                name: 'Quality Check (AQL 2.5)',
                icon: 'bi-check2-circle',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 3850,
                pendingQty: 8650,
                status: 'Passed AQL Inspection',
                badgeClass: 'erp-badge-success',
                location: 'Tirupur QC Lab',
                targetDate: '2026-09-10',
                alerts: 'Zero major defects logged in batch #3 audit.',
                details: 'End-line measurement and shade check verified.'
            },
            'packing': {
                name: 'Packing & Carton Tagging',
                icon: 'bi-archive',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 3200,
                pendingQty: 9300,
                status: 'Polybag & Solid Pack',
                badgeClass: 'erp-badge-purple',
                location: 'Tirupur Packaging Bay',
                targetDate: '2026-09-12',
                alerts: 'Assorted size polybags & shipping barcodes scanned.',
                details: '128 master cartons packed with humidity indicators.'
            },
            'dispatch': {
                name: 'Dispatch & Logistics',
                icon: 'bi-truck',
                buyer: 'Nordic Retail Group',
                poNumber: 'PO-2026-8841',
                style: 'ST-9042 (Woven Casual Shirt)',
                plannedQty: 12500,
                completedQty: 1200,
                pendingQty: 11300,
                status: 'Container #1 Loaded',
                badgeClass: 'erp-badge-accent',
                location: 'Tirupur Dispatch Yard → Chennai Port',
                targetDate: '2026-09-15',
                alerts: 'e-Way bill generated. Export Invoice & Packing list ready.',
                details: 'Final ETA Hamburg Port: 2026-10-02.'
            }
        },
        selectStage(stageKey) {
            this.activeStage = stageKey;
        }
    }));

    // 2. Interactive BOM & Costing Calculator Engine
    Alpine.data('bomCalculator', () => ({
        styleNo: 'ST-9042',
        orderQty: 1000,
        fabricMetersPerPc: 1.5,
        fabricCostPerMeter: 4.80,
        wastagePct: 3.5,
        trimsCostPerPc: 1.20,
        accessoriesCostPerPc: 0.85,
        laborCostPerPc: 2.10,
        jobworkWashingCost: 0.75,
        overheadPct: 8.0,

        get fabricTotalCost() {
            const rawMeters = this.orderQty * this.fabricMetersPerPc;
            const totalMetersWithWastage = rawMeters * (1 + (this.wastagePct / 100));
            return totalMetersWithWastage * this.fabricCostPerMeter;
        },

        get fabricCostPerPc() {
            return (this.fabricMetersPerPc * (1 + (this.wastagePct / 100))) * this.fabricCostPerMeter;
        },

        get directMaterialCostPerPc() {
            return this.fabricCostPerPc + parseFloat(this.trimsCostPerPc || 0) + parseFloat(this.accessoriesCostPerPc || 0);
        },

        get directLaborAndOpsCostPerPc() {
            return parseFloat(this.laborCostPerPc || 0) + parseFloat(this.jobworkWashingCost || 0);
        },

        get baseUnitCost() {
            return this.directMaterialCostPerPc + this.directLaborAndOpsCostPerPc;
        },

        get totalUnitCost() {
            return this.baseUnitCost * (1 + (parseFloat(this.overheadPct || 0) / 100));
        },

        get grandTotalOrderCost() {
            return this.totalUnitCost * this.orderQty;
        }
    }));

    // 3. Configurable Dashboard Engine
    Alpine.data('configurableDashboard', () => ({
        showProduction: true,
        showOrders: true,
        showInventory: true,
        showJobWork: true,
        showCosting: true,
        showAlerts: true,

        toggleWidget(widgetKey) {
            this[widgetKey] = !this[widgetKey];
        }
    }));

    // 4. OCR Simulator Engine
    Alpine.data('ocrScanner', () => ({
        scanning: false,
        scanned: false,
        fileName: 'Supplier_Invoice_TEX_982.pdf',
        extractedData: null,

        runOcr() {
            this.scanning = true;
            this.scanned = false;
            setTimeout(() => {
                this.scanning = false;
                this.scanned = true;
                this.extractedData = {
                    vendor: 'Vardhman Textiles Ltd',
                    invoiceNo: 'VTL-2026-4491',
                    date: '2026-08-18',
                    poReference: 'PO-2026-8841',
                    fabricItem: '100% Cotton Combed Twill 180 GSM',
                    qtyMeters: '18,750 m',
                    unitPrice: '$ 4.80',
                    taxAmount: '$ 4,500.00 (GST 5%)',
                    totalValue: '$ 94,500.00',
                    confidence: '99.4%'
                };
            }, 1200);
        }
    }));

    // 5. Store & Multi-location Filter Engine
    Alpine.data('storeInventory', () => ({
        selectedLocation: 'all',
        items: [
            { code: 'FAB-COT-180', name: '100% Cotton Twill 180GSM', category: 'Fabric', location: 'Tirupur', stock: '24,500 m', reserved: '18,750 m', status: 'Available', alert: false },
            { code: 'FAB-DEN-12OZ', name: 'Ring Spun Denim 12oz', category: 'Fabric', location: 'Bangalore', stock: '14,200 m', reserved: '12,000 m', status: 'Available', alert: false },
            { code: 'BTN-POL-18L', name: 'Recycled Polyester Buttons 18L', category: 'Trims', location: 'Mumbai', stock: '85,000 pcs', reserved: '65,000 pcs', status: 'Available', alert: false },
            { code: 'ZIP-YKK-5VS', name: 'YKK Metal Zipper 5VS 20cm', category: 'Accessories', location: 'Delhi', stock: '3,200 pcs', reserved: '3,000 pcs', status: 'Low Stock', alert: true },
            { code: 'THR-COAT-120', name: 'Coats Dual Duty Thread 120s', category: 'Trims', location: 'Tirupur', stock: '1,400 spools', reserved: '1,200 spools', status: 'Available', alert: false },
            { code: 'LBL-CARE-WOV', name: 'Woven Satin Care Labels', category: 'Trims', location: 'Dubai', stock: '45,000 pcs', reserved: '40,000 pcs', status: 'Available', alert: false }
        ],
        get filteredItems() {
            if (this.selectedLocation === 'all') return this.items;
            return this.items.filter(i => i.location.toLowerCase() === this.selectedLocation.toLowerCase());
        }
    }));
});

// Initialize ApexCharts after DOM load
document.addEventListener('DOMContentLoaded', () => {
    // 1. Production Output Trend Chart
    const prodChartEl = document.querySelector('#productionTrendChart');
    if (prodChartEl && window.ApexCharts) {
        const prodOptions = {
            series: [{
                name: 'Target Qty',
                data: [1200, 1500, 1800, 2000, 2200, 2500, 2800]
            }, {
                name: 'Actual Output',
                data: [1150, 1480, 1820, 1980, 2250, 2460, 2790]
            }],
            chart: {
                type: 'area',
                height: 240,
                toolbar: { show: false },
                background: 'transparent'
            },
            colors: ['#3b82f6', '#10b981'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                labels: { style: { colors: '#94a3b8' } }
            },
            yaxis: {
                labels: { style: { colors: '#94a3b8' } }
            },
            grid: { borderColor: 'rgba(255, 255, 255, 0.06)' },
            theme: { mode: 'dark' },
            legend: { labels: { colors: '#f8fafc' } }
        };
        const prodChart = new window.ApexCharts(prodChartEl, prodOptions);
        prodChart.render();
    }

    // 2. Order Breakdown Donut Chart
    const orderChartEl = document.querySelector('#orderStatusDonutChart');
    if (orderChartEl && window.ApexCharts) {
        const orderOptions = {
            series: [45, 25, 18, 12],
            labels: ['In Cutting & Sewing', 'In Job Work', 'In Finishing & QC', 'Ready for Dispatch'],
            chart: {
                type: 'donut',
                height: 240,
                background: 'transparent'
            },
            colors: ['#3b82f6', '#f59e0b', '#c9a227', '#10b981'],
            stroke: { show: false },
            legend: {
                position: 'bottom',
                labels: { colors: '#f8fafc' }
            },
            theme: { mode: 'dark' }
        };
        const orderChart = new window.ApexCharts(orderChartEl, orderOptions);
        orderChart.render();
    }
});
