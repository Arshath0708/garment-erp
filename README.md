# Garment ERP — Apparel Manufacturing Enterprise Resource Planning

**Garment ERP** is an end-to-end apparel manufacturing management system designed around one central document: **Style / Order → Production → Packing → Export Shipment → Billing**.

```text
Customer / Buyer → Sales Order / PO → Style Master → BOM & Consumption → Material Requirement
                                                                                │
                                                                        Production Plan
                                                                                │
                                            ┌───────────────────────────────────┼───────────────────────────────────┐
                                         Cutting                             Printing                            Embroidery
                                            │                                   │                                   │
                                            └───────────────────────────────────┼───────────────────────────────────┘
                                                                            Stitching
                                                                                │
                                                                        Washing & Finishing
                                                                                │
                                                                          Quality Check
                                                                                │
                                                                             Packing
                                                                                │
                                                                            Shipment
                                                                                │
                                                                        Export Documentation
                                                                                │
                                                                        Invoice & OCR Check
```

---

## 1. Core Architecture & Philosophy

1. **One Order ID Lifecycle**: Once an order (e.g. `PO-2026-8841`) is created, all material, production floor stages, job work, QC, packing, shipment, export documentation, and billing remain connected to that order. Users enter data only once.
2. **Exception-Based UX**: The home dashboard tells managers *"Everything is on track except these items"*, highlighting production delays, fabric shortages, and document mismatches rather than forcing users to manually calculate data across separate screens.
3. **Integrated Intelligent OCR**: Uploaded commercial invoices and Bill of Lading PDFs/Images are scanned using Gemini OCR, and the extracted data is automatically compared against ERP Sales Order values to flag quantity or buyer mismatches.

---

## 2. Key Modules & Features

### A. Style Master & Tech Pack (`/masters/styles`)
- **Specs & Specifications**: Style #, Style Name, Buyer, Garment Category, Season, Colorway, Fit Design, Fabric composition & GSM, Size range, Target Batch Quantity.
- **Tech Pack View**: Detailed technical specifications, seam allowances, wash treatments, construction notes, logo sketch upload, and associated production orders.
- **Full CRUD**: Create, Read, Edit, Update, Delete.

### B. Manufacturing Processes & Floor Tracking (`/manufacturing`)
- **Stage Yield Tracking**: Live progress tracking across **Cutting → Printing/Embroidery → Stitching → Finishing → Quality Control → Packing → Dispatch**.
- **Stage Breakdown Cards**: Live completion percentage bars and quantity yield summaries.
- **Full CRUD**: Create, Read, Edit, Update, Delete, Modal Stage Yield Update.

### C. BOM & Material Requirement Calculator (`/masters/bom`)
- **Automated Calculation**: 
$$\text{Order Qty} \times \text{Consumption} = \text{Required Material}$$
$$\text{Required Material} - \text{Available Stock} = \text{Purchase Requirement}$$

### D. Intelligent OCR Mismatch Verification Desk (`/export/ocr`)
- **Document Scanner**: Upload PDFs/Images of Commercial Invoices, Packing Lists, or Shipping Bills.
- **ERP Mismatch Checker**: Automatically compares extracted invoice quantities and buyer names against ERP Sales Orders to display green matches or orange/red mismatch alerts.

### E. Exception-Based Executive Dashboard (`/dashboard`)
- **Today's Production**: Live stage progress for order **PO-00452 (10,000 pcs)** across Cutting (85%), Printing (82%), Stitching (65%), Finishing (58%), and Packing (55%).
- **Important Alerts**: Action alerts for pending cutting, stitching delays, fabric shortages, jobber material pending, OCR invoice mismatches, and upcoming shipments.

---

## 3. Technology Stack

- **Framework**: Laravel 12
- **Language**: PHP 8.3+
- **Database**: SQLite / MySQL 8
- **Frontend**: Blade · Vanilla CSS / Custom Dual Theme Architecture · Bootstrap 5 · AdminLTE 4 · Vite 7
- **Icons**: Bootstrap Icons
- **OCR Engine**: Google Gemini API / Custom Parser Service

---

## 4. Local Development & Server Setup

### Prerequisites
- PHP 8.2 or 8.3 (`C:\php\php.exe`)
- Node.js 18+ and npm
- Composer

### Installation Steps

1. **Clone / Open Workspace**:
   ```powershell
   cd C:\Projects\ERP\garment-erp
   ```

2. **Install PHP & Node Dependencies**:
   ```powershell
   C:\php\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install
   npm install
   ```

3. **Build Frontend Production Assets**:
   ```powershell
   npm run build
   ```

4. **Start PHP Development Server**:
   ```powershell
   C:\php\php.exe artisan serve
   ```

5. **Access Application**:
   Open **[http://127.0.0.1:8000](http://127.0.0.1:8000)** in your web browser.

---

## 5. Development Credentials

- **Super Admin Email**: `admin@garment.com`
- **Super Admin Password**: `garment@123`

---

## 6. Project Structure

```text
garment-erp/
├── app/
│   ├── Http/Controllers/
│   │   ├── Masters/
│   │   │   ├── GarmentStyleController.php    ← Style Master & Tech Pack CRUD
│   │   │   ├── CategoryController.php        ← Category Master
│   │   │   ├── DocumentFormatController.php  ← Order Format Master
│   │   │   └── BOMController.php             ← BOM Calculator
│   │   ├── Manufacturing/
│   │   │   └── ManufacturingController.php   ← Manufacturing Floor Stages CRUD
│   │   ├── Export/
│   │   │   └── ExportDocumentOcrController.php ← OCR Scanner & ERP Mismatch Checker
│   │   └── DashboardController.php
│   └── Models/
│       ├── GarmentStyle.php
│       ├── ProductionOrder.php
│       └── OrderConfirmation.php
├── resources/
│   ├── css/
│   │   └── garment-erp.css                   ← Dual Theme Stylesheet (Light & Dark)
│   └── views/
│       ├── dashboard.blade.php               ← Exception-Based Dashboard
│       ├── layouts/
│       │   ├── app.blade.php                 ← Dual Component Layout
│       │   ├── header.blade.php              ← Theme Toggle Header
│       │   └── sidebar.blade.php             ← Section 14 Navigation Sidebar
│       ├── masters/
│       │   ├── styles/                       ← Style CRUD Views
│       │   ├── categories/                   ← Category Views
│       │   ├── formats/                      ← Order Format Views
│       │   └── bom/                          ← BOM Calculator View
│       └── manufacturing/                    ← Manufacturing Floor CRUD Views
└── routes/
    └── web.php                               ← 214 Verified Application Routes
```

---

## 7. License

Proprietary Garment Manufacturing ERP System. All rights reserved.
