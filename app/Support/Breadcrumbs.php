<?php

namespace App\Support;

/**
 * Home → module → screen → action crumbs for the app header.
 * Matched longest-prefix first so reports.outstanding wins over reports.
 */
class Breadcrumbs
{
    /**
     * @return list<array{label: string, url: ?string}>
     */
    public static function trail(): array
    {
        $home = ['label' => 'Home', 'url' => route('dashboard')];

        $name = request()->route()?->getName();
        if (! is_string($name) || $name === 'dashboard') {
            return [$home, ['label' => 'Dashboard', 'url' => null]];
        }

        $map = self::map();
        $matched = null;
        $matchedLen = 0;

        foreach ($map as $prefix => $crumb) {
            $len = strlen($prefix);
            if ($len > $matchedLen && (str_starts_with($name, $prefix.'.') || $name === $prefix)) {
                $matched = $crumb;
                $matchedLen = $len;
            }
        }

        $trail = [$home];

        if ($matched) {
            $trail[] = ['label' => $matched['module'], 'url' => $matched['module_url']];
            $isCanonical = $name === $matched['index'];
            $trail[] = [
                'label' => $matched['screen'],
                'url' => $isCanonical ? null : route($matched['index']),
            ];

            if (! $isCanonical) {
                $action = self::actionLabel($name);
                if ($action !== null) {
                    $trail[] = ['label' => $action, 'url' => null];
                }
            }

            return $trail;
        }

        $trail[] = ['label' => self::fallbackLabel($name), 'url' => null];

        return $trail;
    }

    /**
     * @return array<string, array{module: string, module_url: string, screen: string, index: string}>
     */
    private static function map(): array
    {
        return [
            'masters.categories' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Categories', 'index' => 'masters.categories.index'],
            'masters.formats' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Order Formats', 'index' => 'masters.formats.index'],
            'masters.buyers' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Customers / Buyers', 'index' => 'masters.buyers.index'],
            'masters.styles' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Style Master & Tech Pack', 'index' => 'masters.styles.index'],
            'masters.bom' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'BOM & Consumption', 'index' => 'masters.bom.index'],
            'style-costings' => ['module' => 'Masters', 'module_url' => route('style-costings.index'), 'screen' => 'Style Costing', 'index' => 'style-costings.index'],
            'masters.products' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Item Master (Trims/Fabric)', 'index' => 'masters.products.index'],
            'masters.suppliers' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Suppliers Master', 'index' => 'masters.suppliers.index'],
            'masters.jobbers' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Job Worker / Jobbers', 'index' => 'masters.jobbers.index'],
            'masters.agents' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Agents', 'index' => 'masters.agents.index'],
            'masters.markups' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'Markup', 'index' => 'masters.markups.index'],
            'masters.fob-values' => ['module' => 'Masters', 'module_url' => route('masters.categories.index'), 'screen' => 'FOB Values', 'index' => 'masters.fob-values.index'],
            'sales.inquiries' => ['module' => 'Sales & Orders', 'module_url' => route('sales.inquiries.index'), 'screen' => 'Enquiry & Quotations', 'index' => 'sales.inquiries.index'],
            'sales.order-confirmations' => ['module' => 'Sales & Orders', 'module_url' => route('sales.inquiries.index'), 'screen' => 'Sales Order / PO', 'index' => 'sales.order-confirmations.index'],
            'work-orders' => ['module' => 'Manufacturing Processes', 'module_url' => route('work-orders.index'), 'screen' => 'Work Orders', 'index' => 'work-orders.index'],
            'time-and-action' => ['module' => 'Manufacturing Processes', 'module_url' => route('work-orders.index'), 'screen' => 'Time & Action', 'index' => 'time-and-action.index'],
            'production-lines' => ['module' => 'Manufacturing Processes', 'module_url' => route('work-orders.index'), 'screen' => 'Line efficiency', 'index' => 'production-lines.index'],
            'manufacturing' => ['module' => 'Manufacturing Processes', 'module_url' => route('manufacturing.index'), 'screen' => 'Production Planning', 'index' => 'manufacturing.index'],
            'inventory' => ['module' => 'Inventory & Job Work', 'module_url' => route('inventory.index'), 'screen' => 'Fabric & Accessory Stock', 'index' => 'inventory.index'],
            'job-work' => ['module' => 'Inventory & Job Work', 'module_url' => route('job-work.index'), 'screen' => 'Job Work Issue / Receive', 'index' => 'job-work.index'],
            'procurement.purchase-orders' => ['module' => 'Inventory & Job Work', 'module_url' => route('inventory.index'), 'screen' => 'Fabric & Trims PO', 'index' => 'procurement.purchase-orders.index'],
            'procurement.inward-entries' => ['module' => 'Inventory & Job Work', 'module_url' => route('inventory.index'), 'screen' => 'Goods Inward', 'index' => 'procurement.inward-entries.index'],
            'export.packing' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Packing & Cartons', 'index' => 'export.packing.index'],
            'export.documents' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Export Docs & Invoices', 'index' => 'export.documents.index'],
            'export.ocr' => ['module' => 'Intelligent OCR & Reports', 'module_url' => route('export.ocr.index'), 'screen' => 'OCR Document Verification', 'index' => 'export.ocr.index'],
            'finance.purchase-bills' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Billing & Payments', 'index' => 'finance.purchase-bills.index'],
            'finance.debit-notes' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Debit Notes', 'index' => 'finance.debit-notes.index'],
            'finance.supplier-payments' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Supplier Payments', 'index' => 'finance.supplier-payments.index'],
            'finance.buyer-receipts' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Buyer Receipts', 'index' => 'finance.buyer-receipts.index'],
            'finance.agent-commission' => ['module' => 'Packing, Shipment & Billing', 'module_url' => route('export.packing.index'), 'screen' => 'Agent Commission', 'index' => 'finance.agent-commission.index'],
            'reports.outstanding' => ['module' => 'Intelligent OCR & Reports', 'module_url' => route('export.ocr.index'), 'screen' => 'Outstanding', 'index' => 'reports.outstanding.index'],
            'reports.order-profit' => ['module' => 'Intelligent OCR & Reports', 'module_url' => route('export.ocr.index'), 'screen' => 'Profit per order', 'index' => 'reports.order-profit'],
            'reports.factory-board' => ['module' => 'Intelligent OCR & Reports', 'module_url' => route('export.ocr.index'), 'screen' => 'Factory board', 'index' => 'reports.factory-board'],
            'reports' => ['module' => 'Intelligent OCR & Reports', 'module_url' => route('export.ocr.index'), 'screen' => 'ERP Reports & Outstanding', 'index' => 'reports.index'],
            'user-management.users' => ['module' => 'Account', 'module_url' => route('profile.edit'), 'screen' => 'Users', 'index' => 'user-management.users.index'],
            'user-management.roles' => ['module' => 'Account', 'module_url' => route('profile.edit'), 'screen' => 'Roles', 'index' => 'user-management.roles.index'],
            'user-management.permissions' => ['module' => 'Account', 'module_url' => route('profile.edit'), 'screen' => 'Permissions', 'index' => 'user-management.permissions.index'],
            'user-management.company-profile' => ['module' => 'Account', 'module_url' => route('profile.edit'), 'screen' => 'Company Profile', 'index' => 'user-management.company-profile.edit'],
            'profile' => ['module' => 'Account', 'module_url' => route('profile.edit'), 'screen' => 'Profile & Settings', 'index' => 'profile.edit'],
        ];
    }

    private static function actionLabel(string $name): ?string
    {
        return match (true) {
            str_ends_with($name, '.create') => 'New',
            str_ends_with($name, '.edit') => 'Edit',
            str_ends_with($name, '.show') => 'View',
            str_ends_with($name, '.export') => 'Export',
            default => null,
        };
    }

    private static function fallbackLabel(string $name): string
    {
        $last = last(explode('.', $name));

        return str_replace('-', ' ', ucfirst($last));
    }
}
