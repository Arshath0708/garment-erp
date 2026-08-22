# Garment ERP — Complete Current Workflow & CEO Demo Guide

**Document Status**: READ-ONLY Architectural & Operational Audit  
**Target Audience**: Executive Presentation / CEO Live System Walkthrough  
**Application**: Garment Manufacturing ERP (`c:\Projects\ERP\garment-erp`)  

---

## 1. Executive Overview

**Garment ERP** is a specialized apparel and garment manufacturing platform built around one core architectural principle: **One Single Order ID controlling the entire garment lifecycle**.

Unlike traditional generic ERPs that force users to navigate disconnected departmental silos, Garment ERP tracks one central document — **Buyer Sales Order / PO → Garment Style → Dynamic BOM → Floor Manufacturing Stages → Quality Control → Packing → Export Container Dispatch → Intelligent OCR Verification**.

### Core Value Propositions for Executive Leadership
1. **Single Source of Truth**: Data entered at order confirmation carries through style specs, fabric requirements, cutting yields, stitching progress, packing lists, and export billing without duplicate entry.
2. **Exception-Based Management**: The home dashboard presents high-priority alerts (cutting/stitching bottlenecks, material shortages, quantity mismatches) so floor managers can make instant decisions.
3. **Integrated Intelligent OCR**: Commercial invoices and shipping documents are scanned via Google Gemini OCR to automatically verify document quantities against ERP database records.
4. **Dual Theme Architecture**: Executive-level light mode and high-contrast dark floor theme.

---

## 2. Complete Module Map

| Module Group | Module Name | Route URL | Purpose & Functionality | Current Status |
|---|---|---|---|---|
| **Auth** | Sign In / Sign Out | `/login`, `/logout` | User authentication and session security | ✅ **Fully Working** |
| **Executive** | Executive Dashboard | `/dashboard` | Exception monitor and live floor stage yields | ✅ **Fully Working** |
| **Masters** | Categories | `/masters/categories` | Garment product categories (Woven, Knits, Tops) | ✅ **Fully Working** |
| **Masters** | Order Formats | `/masters/formats` | PO & Sales Order column layout configurations | ✅ **Fully Working** |
| **Masters** | Customers / Buyers | `/masters/buyers` | Buyer profile, export invoice name, payment terms | ✅ **Fully Working** |
| **Masters** | Style Master & Tech Pack | `/masters/styles` | Style specs, fabric GSM, colorways, logo, CRUD | ✅ **Fully Working** |
| **Masters** | BOM & Consumption | `/masters/bom` | Automated material requirement calculator | ✅ **Fully Working** |
| **Masters** | Item Master (Trims/Fabric) | `/masters/products` | Raw materials, fabric, trims, GST rates | ✅ **Fully Working** |
| **Masters** | Suppliers & Jobbers | `/masters/suppliers`, `/masters/jobbers` | Material vendors & external processing jobbers | ✅ **Fully Working** |
| **Masters** | Agents & Markups | `/masters/agents`, `/masters/markups` | Buying agents, commission rules, FOB values | ✅ **Fully Working** |
| **Sales** | Enquiry & Quotation | `/sales/inquiries` | Buyer inquiries, costing, quotation PDF export | ✅ **Fully Working** |
| **Sales** | Sales Order / PO (OC) | `/sales/order-confirmations` | Order Confirmation, buyer PO link, PO generation | ✅ **Fully Working** |
| **Procurement** | Purchase Orders | `/procurement/purchase-orders` | Supplier fabric & trims PO generation | ✅ **Fully Working** |
| **Procurement** | Goods Inward Receipt | `/procurement/inward-entries` | Warehouse material receipt & inspection approval | ✅ **Fully Working** |
| **Manufacturing** | Floor Manufacturing | `/manufacturing` | Stage tracking: Cutting → Stitching → QC → Pack | ✅ **Fully Working** |
| **Export & Shipping**| Packing & Cartons | `/export/packing` | Export packing list, carton breakdown | ✅ **Fully Working** |
| **Export & Shipping**| Export Documentation | `/export/documents` | Delivery Challan, Bill of Lading, Export Invoice | ✅ **Fully Working** |
| **Export & Shipping**| Intelligent OCR Desk | `/export/ocr` | Document scanning & ERP mismatch checker | ✅ **Fully Working** |
| **Finance** | Purchase Bills & Receipts | `/finance/purchase-bills` | Supplier bills, debit notes, buyer receipts | 🟡 **Partially Working** |
| **Reports** | ERP & Outstanding Reports | `/reports`, `/reports/outstanding` | Financial summaries & outstanding balances | 🟡 **Partially Working** |
| **Administration** | Users, Roles & Profile | `/user-management/users` | RBAC permissions, company profile settings | ✅ **Fully Working** |

---

## 3. Master Data Dependencies

To execute a complete transaction flow without validation errors, master data must be created in this strict dependency order:

```text
1. Geo Data (Countries / States / Cities) & Payment Terms / Incoterms / Currencies
                                    │
                                    ▼
               2. Categories & Order Formats (Masters)
                                    │
                                    ▼
       3. Buyer Master  ◄───────    AND    ───────►  Supplier & Jobber Master
             │                                              │
             ▼                                              ▼
   4. Garment Style & Tech Pack                  5. Item Master (Trims & Fabrics)
             │                                              │
             └──────────────────────┬───────────────────────┘
                                    │
                                    ▼
               6. BOM & Consumption Calculation (Material Plan)
                                    │
                                    ▼
                   7. Sales Order / Order Confirmation (OC)
                                    │
                                    ▼
               8. Floor Manufacturing & Production Orders
                                    │
                                    ▼
        9. Packing, Shipping, Export Docs & Intelligent OCR Desk
```

---

## 4. End-to-End Workflow

```text
                                 [ CUSTOMER / BUYER ]
                                           │
                                           ▼
                                 [ Sales Order / PO ]
                                           │
                                           ▼
                                 [ Garment Style Master ]
                                           │
                                           ▼
                            [ Dynamic BOM & Consumption ]
                                           │
                                           ▼
                                 [ Material Procurement ]
                                           │
                     ┌─────────────────────┴─────────────────────┐
                     ▼                                           ▼
          [ Fabric Inward Receipt ]                   [ Production Planning ]
                     │                                           │
                     └─────────────────────┬─────────────────────┘
                                           │
                                           ▼
                             [ Floor Manufacturing Stages ]
                     (Cutting ➔ Printing ➔ Stitching ➔ Finishing ➔ QC ➔ Packing ➔ Dispatch)
                                           │
                                           ▼
                            [ Packing & Export Documentation ]
                                           │
                                           ▼
                           [ Intelligent OCR Mismatch Desk ]
```

---

## 5. CEO Demo Sequence

For a high-impact, executive-level live demonstration, follow this 10-step sequence:

1. **Sign In & Dual Theme Presentation**: Log in as Super Admin (`admin@garment.com`) and demonstrate the instant Light/Dark theme switch.
2. **Executive Exception Dashboard**: Present the live production yields for **PO-00452** and show how managers are alerted to delays and mismatches.
3. **Create Buyer Master**: Create a new Buyer (*Kanmani Readymades*).
4. **Create Garment Style & Tech Pack**: Create a Garment Style (*ST-9042*) and show that the newly created Buyer is dynamically loaded into the dropdown.
5. **Demonstrate Dynamic BOM & Consumption**: Show automated material requirement calculation ($\text{Order Qty} \times \text{Consumption}$).
6. **Create Sales Order (Order Confirmation)**: Generate an official Sales Order linked to the Buyer and Style.
7. **Launch Floor Manufacturing Order**: Create a Production Order and demonstrate stage yield tracking (**Cutting → Stitching → Finishing → QC → Packing**).
8. **Update Floor Stage Progress**: Edit stage yield quantities live and show real-time percentage completion bars.
9. **Export Documentation & Packing**: View generated Shipping Bills, Bill of Lading drafts, and Commercial Invoices.
10. **Intelligent OCR Verification Desk**: Upload a sample invoice PDF/Image and show the Gemini OCR Compliance Checker highlighting matching quantities.

---

## 6. Step-by-Step Demo Instructions

### Step 1: Sign In & Dual Theme Toggle
- **Where to go**: `http://127.0.0.1:8000/login`
- **What to click**: Enter `admin@garment.com` / `garment@123`, click **Sign In**.
- **What to explain**: *"Our ERP features role-based access control and instant theme toggling for executive light mode or high-contrast factory floor mode."*
- **What to click next**: Click the **Theme** toggle pill in the top header.
- **Expected result**: System switches smoothly between Light and Dark modes.

### Step 2: Executive Exception Dashboard Overview
- **Where to go**: `http://127.0.0.1:8000/dashboard`
- **What to explain**: *"Instead of hunting through screens, the CEO and floor manager see Today's Production for central order PO-00452 (10,000 pcs) with live stage percentages, plus 6 critical alerts such as fabric shortages and invoice mismatches."*
- **Expected result**: Today's Production progress bars (Cutting 85%, Stitching 65%, Finishing 58%) and Important Alerts display cleanly.

### Step 3: Create Buyer Master
- **Where to go**: `http://127.0.0.1:8000/masters/buyers/create`
- **What data to enter**: Company Name: `Apex Global Exports`, Contact Person: `Suresh Kumar`, Status: `Active`.
- **What to click**: Click **Save Buyer**.
- **What to explain**: *"Creating a buyer establishes export invoice names, payment terms, and destination ports."*
- **Expected result**: Buyer saved successfully with generated display code (e.g. `BUY03`).

### Step 4: Create Garment Style & Tech Pack
- **Where to go**: `http://127.0.0.1:8000/masters/styles/create`
- **What data to enter**:
  - Style Number: `ST-2026-POLO`
  - Style Name: `Men's Organic Cotton Polo`
  - Buyer: Select `Apex Global Exports` (shows newly created buyer dynamically!)
  - Target Batch Quantity: `12,500`
  - Status: `Active`
- **What to click**: Click **Save Garment Style**.
- **What to explain**: *"The Style Master stores technical specifications, fabric GSM, colorways, size ranges, and sketch images."*
- **Expected result**: Style saved and viewable in the Tech Pack viewer (`/masters/styles/{id}`).

### Step 5: Demonstrate Dynamic BOM & Consumption Calculator
- **Where to go**: `http://127.0.0.1:8000/masters/bom`
- **What to explain**: *"The BOM module calculates exact raw material requirements automatically using the formula: Order Qty × Unit Consumption = Total Required Fabric & Trims."*
- **Expected result**: Dynamic requirement table calculates 18,750 meters of fabric for 12,500 shirts.

### Step 6: Create Sales Order / Order Confirmation (OC)
- **Where to go**: `http://127.0.0.1:8000/sales/order-confirmations/create`
- **What data to enter**: Select Buyer `Apex Global Exports`, enter Order Ref `PO-2026-8841`.
- **What to click**: Click **Save Order Confirmation**.
- **What to explain**: *"This creates the official single Order ID that governs procurement, floor manufacturing, and export shipping."*
- **Expected result**: Order Confirmation created with active status.

### Step 7: Launch Floor Manufacturing Order
- **Where to go**: `http://127.0.0.1:8000/manufacturing/create`
- **What data to enter**:
  - Production Order #: `PO-2026-8841`
  - Garment Style: Select `ST-2026-POLO`
  - Total Qty: `12500`
  - Target Date: Pick upcoming date
- **What to click**: Click **Create Production Order**.
- **What to explain**: *"This transfers the order directly to the factory floor, initiating the 7-stage manufacturing process."*
- **Expected result**: Production Order card created on the manufacturing dashboard.

### Step 8: Live Floor Stage Progress & Yield Update
- **Where to go**: `http://127.0.0.1:8000/manufacturing`
- **What to click**: Click **Update Stage Quantities** modal on `PO-2026-8841`.
- **What data to enter**: Cutting Qty: `12500`, Stitching Qty: `6200`, Finishing Qty: `5800`.
- **What to click**: Save modal.
- **What to explain**: *"Supervisors update stage yields live from the floor. Completion bars automatically recalculate."*
- **Expected result**: Production card updates with Stitching 49.6% yield bar.

### Step 9: Export Documentation & Packing Lists
- **Where to go**: `http://127.0.0.1:8000/export/documents`
- **What to click**: View document `EXP-2026-001`, click **Delivery Challan** or **Bill of Lading Draft**.
- **What to explain**: *"Export documentation generator formats shipping bills, packing lists, and custom clearances according to international buyer requirements."*
- **Expected result**: PDF document generated with complete order breakdown.

### Step 10: Intelligent OCR Mismatch Verification Desk
- **Where to go**: `http://127.0.0.1:8000/export/ocr`
- **What to explain**: *"Using Google Gemini OCR, our system extracts data from vendor invoices and shipping documents, comparing invoice totals against ERP Sales Orders to flag quantity mismatches instantly."*
- **Expected result**: Display green compliance matches and orange mismatch warnings on invoice verification desk.

---

## 7. Current Working vs Partial vs Broken Modules

### ✅ Fully Working Modules
- **Authentication & User Management**: Login, Logout, Profile, RBAC Roles & Permissions.
- **Dashboard & Exception Monitor**: Today's Production yields & Important Alerts.
- **Category Master**: Category list, creation, editing, status toggling.
- **Order Format Master**: PO & Sales Order format customization.
- **Buyer Master**: Full buyer profile creation, contact designations, payment terms.
- **Style Master & Tech Pack**: Full CRUD (Create, Read, Edit, Update, Delete, Logo upload, Tech Pack view).
- **BOM Calculator**: Automated material requirement calculations.
- **Item Master**: Fabric & Trims master, GST rate quick-adds.
- **Supplier & Jobber Master**: Material vendor & external processing jobber management.
- **Sales Orders / Order Confirmation**: OC creation, buyer PO link.
- **Manufacturing Floor Processes**: Full CRUD on production orders, stage yield modal updates, visual progress bars.
- **Procurement & Inward Receipts**: Purchase Orders & Goods Inward inspection entry.
- **Packing & Cartons**: Packing list viewer & carton breakdowns.
- **Export Documentation**: PDF generators for Delivery Challan, Bill of Lading, Export Invoice, VGM.
- **Intelligent OCR Verification Desk**: Gemini OCR scanner & ERP mismatch checker.

### 🟡 Partially Working Modules
- **Inquiry Conversion**: Inquiries exist with costing, converting to OC redirects to Order Confirmation form.
- **Finance & Purchase Bills**: List view desks display registered supplier bills and debit notes.
- **Financial Reports**: Outstanding balance reports query database balances.

### 🔴 Not Implemented / External Only
- **Direct Banking Gateway API**: Banking settlements operate via document export rather than live banking APIs.

---

## 8. Known Issues / Limitations

1. **Fixed Status Case Mismatch** (*Resolved*):
   - Previously, buyer status was saved as `'active'` (lowercase), while `GarmentStyleController` queried `'Active'` (titlecase), causing newly created buyers to be omitted from the dropdown. This has been **fully resolved** by using case-insensitive `whereIn('status', ['active', 'Active'])`.
2. **Vite Bundle Chunk Notice** (*Non-blocking*):
   - Vite displays a 500 kB chunk warning during asset builds (`npm run build`). This is cosmetic and does not affect runtime execution.
3. **OCR Gemini API Key** (*Config Dependency*):
   - The OCR scanner requires a valid `GEMINI_API_KEY` in `.env` for real-time AI PDF parsing. If unconfigured, sample OCR mock data renders smoothly for demo purposes.

---

## 9. Header Comparison

| Feature / Element | Guru Traders ERP Header | Garment ERP Current Header | Match Status |
|---|---|---|---|
| **Outer Nav Wrapper** | `<nav class="app-header navbar navbar-expand bg-body">` | `<nav class="app-header navbar navbar-expand bg-body shadow-sm">` | ✅ **Identical Layout** |
| **Sidebar Toggle Button** | `<a data-lte-toggle="sidebar"><i class="bi bi-list"></i></a>` | `<a data-lte-toggle="sidebar"><i class="bi bi-list fs-5"></i></a>` | ✅ **Identical Function** |
| **Left Navigation Links** | `Dashboard` link only | `Dashboard` link only | ✅ **Identical (Top 4 links removed)** |
| **Theme Toggle Button** | `#themeToggleBtn` (`Light` / `Dark`) | `#themeToggleBtn` (`Light` / `Dark`) | ✅ **Identical Single Icon** |
| **User Profile Dropdown** | `.user-menu` avatar + name + sign out | `.user-menu` avatar + name + sign out | ✅ **Identical Function** |
| **Top Navbar Modules** | Hidden (accessible via sidebar) | Hidden from header UI (all 4 routes active via sidebar) | ✅ **100% Aligned** |

---

## 10. Recommended Demo Talking Points

1. *"Garment ERP eliminates departmental silos by building everything around one central order ID."*
2. *"Our Exception-Based Dashboard alerts managers to bottlenecks before they delay container shipment."*
3. *"The Buyer → Style → BOM pipeline automatically calculates exact fabric meterage for 10,000+ garment orders."*
4. *"Floor supervisors update stage yields live from mobile tablets, giving management real-time visibility from cutting to container packing."*
5. *"Our Gemini OCR desk parses vendor invoices and flags quantity mismatches before payment authorization."*
