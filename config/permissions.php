<?php

/*
|--------------------------------------------------------------------------
| Permission Registry
|--------------------------------------------------------------------------
|
| Single source of truth for every permission in the application.
|
| Permissions are NOT created by hand through the UI. They are declared here
| and pushed into the database by PermissionsSeeder (or `php artisan
| permission:sync`). A permission that is not in this file does not exist.
|
| Why: `@can('product.view')` in a Blade file and a hand-typed
| `prodcut.view` row in the database fail silently — the gate just returns
| false and the menu item disappears with no error. Declaring them here means
| the string in the code and the string in the database come from one place.
|
| To add a module: add it under the right group below, then run
| `php artisan permission:sync`.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Super Admin account
    |--------------------------------------------------------------------------
    | Override in .env for anything other than local development.
    */
    'super_admin' => [
        'email'    => env('SUPER_ADMIN_EMAIL', 'admin@gurutraders.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'Guru@123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default actions
    |--------------------------------------------------------------------------
    | Used when a module does not declare its own "actions" key.
    */
    'default_actions' => ['view', 'create', 'edit', 'delete'],

    /*
    |--------------------------------------------------------------------------
    | Action labels (for the role permission matrix UI)
    |--------------------------------------------------------------------------
    */
    'action_labels' => [
        'view'     => 'View',
        'create'   => 'Create',
        'edit'     => 'Edit',
        'delete'   => 'Delete',
        'approve'  => 'Approve',
        'export'   => 'Export',
        'generate' => 'Generate',
        'download' => 'Download',
        'restore'  => 'Restore',
        'sync'     => 'Sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules, grouped for the UI
    |--------------------------------------------------------------------------
    | key    => permission prefix, e.g. "product" => product.view, product.create
    | label  => shown in the permission matrix
    | actions=> optional override of default_actions
    | built  => false means the screen does not exist yet; it still gets a
    |           permission so roles can be configured ahead of the build,
    |           and the matrix shows it greyed out.
    */
    'groups' => [

        'User Management' => [
            'user'       => ['label' => 'Users'],
            'role'       => ['label' => 'Roles'],
            'permission' => ['label' => 'Permissions', 'actions' => ['view', 'sync']],
        ],

        'Masters' => [
            'company'         => ['label' => 'Company',          'built' => false],
            'category'        => ['label' => 'Category',         'built' => false],
            'product'         => ['label' => 'Product',          'built' => false],
            'buyer'           => ['label' => 'Buyer',            'built' => false],
            'supplier'        => ['label' => 'Supplier (Jobber)', 'built' => false],
            'supplier-rate'   => ['label' => 'Supplier Rates',   'built' => false],
            'agent'           => ['label' => 'Agent',            'built' => false],
            'brand'           => ['label' => 'Brand',            'built' => false],
            'warehouse'       => ['label' => 'Warehouse',        'built' => false],
            'document-format' => ['label' => 'Document Formats', 'built' => false],
        ],

        'General Setup' => [
            'country'         => ['label' => 'Country',         'built' => false],
            'currency'        => ['label' => 'Currency',        'built' => false],
            'port'            => ['label' => 'Port',            'built' => false],
            'unit'            => ['label' => 'Unit',            'built' => false],
            'size'            => ['label' => 'Size',            'built' => false],
            'colour'          => ['label' => 'Colour',          'built' => false],
            'hsn-code'        => ['label' => 'HSN Code',        'built' => false],
            'incoterm'        => ['label' => 'Incoterms',       'built' => false],
            'payment-term'    => ['label' => 'Payment Terms',   'built' => false],
            'shipment-method' => ['label' => 'Shipment Method', 'built' => false],
            'price-band'      => ['label' => 'Price Band',      'built' => false],
        ],

        'Transactions' => [
            'inquiry'            => ['label' => 'Inquiry',            'actions' => ['view', 'create', 'edit', 'delete', 'export'], 'built' => false],
            'quotation'          => ['label' => 'Quotation',          'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'], 'built' => false],
            'order-confirmation' => ['label' => 'Order Confirmation', 'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'], 'built' => false],
            'sample-approval'    => ['label' => 'Sample Approval',    'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'purchase-order'     => ['label' => 'Purchase Order',     'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'], 'built' => false],
            'material-issue'     => ['label' => 'Material Issue',     'built' => false],
            'inward-entry'       => ['label' => 'Inward Entry',       'built' => false],
            'quality-check'      => ['label' => 'Quality Check',      'actions' => ['view', 'create', 'edit', 'approve'], 'built' => false],
            'debit-note'         => ['label' => 'Debit Note',         'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'packing'            => ['label' => 'Packing',            'built' => false],
            'shipment'           => ['label' => 'Shipment',           'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'], 'built' => false],
        ],

        'Export Documents' => [
            'export-document' => ['label' => 'Export Documents', 'actions' => ['view', 'generate', 'download'], 'built' => false],
        ],

        'Accounts' => [
            'purchase-bill'    => ['label' => 'Purchase Bills',    'built' => false],
            'payment'          => ['label' => 'Payments',          'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'foreign-payment'  => ['label' => 'Foreign Payments',  'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'agent-commission' => ['label' => 'Agent Commission',  'built' => false],
            'credit-note'      => ['label' => 'Credit Note',       'built' => false],
            'outstanding'      => ['label' => 'Outstanding',       'actions' => ['view', 'export'], 'built' => false],
        ],

        'Reports' => [
            'report' => ['label' => 'Reports', 'actions' => ['view', 'export'], 'built' => false],
        ],

        'Settings' => [
            'setting'       => ['label' => 'Application Settings', 'actions' => ['view', 'edit'], 'built' => false],
            'number-series' => ['label' => 'Number Series',        'actions' => ['view', 'edit'], 'built' => false],
            'activity-log'  => ['label' => 'Activity Logs',        'actions' => ['view'], 'built' => false],
            'backup'        => ['label' => 'Backup & Restore',     'actions' => ['view', 'create', 'restore'], 'built' => false],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | System roles
    |--------------------------------------------------------------------------
    | These roles are seeded and protected: they cannot be renamed or deleted
    | through the UI, because route middleware and Gate checks reference them
    | by name. Roles created through the UI are freely editable.
    |
    | permissions:
    |   '*'                      => every permission (Super Admin)
    |   ['module.*', 'x.view']   => wildcard per module, or exact permission
    */
    'roles' => [

        'Super Admin' => [
            'description' => 'Full system access including permission management.',
            'permissions' => '*',
        ],

        'Admin' => [
            'description' => 'Full operational access. Cannot manage permissions.',
            'permissions' => [
                'user.*', 'role.view',
                'company.*', 'category.*', 'product.*', 'buyer.*', 'supplier.*',
                'supplier-rate.*', 'agent.*', 'brand.*', 'warehouse.*', 'document-format.*',
                'country.*', 'currency.*', 'port.*', 'unit.*', 'size.*', 'colour.*',
                'hsn-code.*', 'incoterm.*', 'payment-term.*', 'shipment-method.*', 'price-band.*',
                'inquiry.*', 'quotation.*', 'order-confirmation.*', 'sample-approval.*',
                'purchase-order.*', 'material-issue.*', 'inward-entry.*', 'quality-check.*',
                'debit-note.*', 'packing.*', 'shipment.*', 'export-document.*',
                'purchase-bill.*', 'payment.*', 'foreign-payment.*', 'agent-commission.*',
                'credit-note.*', 'outstanding.*', 'report.*',
                'setting.*', 'number-series.*', 'activity-log.*', 'backup.*',
            ],
        ],

        'Merchandising & Manufacturing' => [
            'description' => 'Handles inquiry to purchase order, suppliers and products.',
            'permissions' => [
                'category.view', 'product.*', 'buyer.view', 'supplier.*', 'supplier-rate.*',
                'agent.view', 'brand.view',
                'country.view', 'currency.view', 'port.view', 'unit.view', 'size.view',
                'colour.view', 'hsn-code.view', 'incoterm.view', 'payment-term.view',
                'inquiry.*', 'quotation.*', 'order-confirmation.*', 'sample-approval.*',
                'purchase-order.*', 'material-issue.*',
                'inward-entry.view', 'shipment.view',
                'report.view', 'report.export',
            ],
        ],

        'Accounts' => [
            'description' => 'Bills, payments, commission and outstanding.',
            'permissions' => [
                'buyer.view', 'supplier.view', 'agent.view',
                'purchase-order.view', 'inward-entry.view', 'shipment.view',
                'purchase-bill.*', 'payment.*', 'foreign-payment.*',
                'agent-commission.*', 'debit-note.*', 'credit-note.*', 'outstanding.*',
                'report.view', 'report.export',
            ],
        ],

        'Export Documentation & Foreign Payment' => [
            'description' => 'Shipment, export documents and foreign payment realisation.',
            'permissions' => [
                'buyer.view', 'product.view', 'category.view',
                'port.view', 'incoterm.view', 'currency.view', 'shipment-method.view',
                'order-confirmation.view', 'packing.view',
                'shipment.*', 'export-document.*',
                'foreign-payment.*',
                'report.view', 'report.export',
            ],
        ],

        'Packing' => [
            'description' => 'Packing lists, cartons and markings.',
            'permissions' => [
                'product.view', 'buyer.view', 'warehouse.view',
                'size.view', 'colour.view', 'unit.view',
                'order-confirmation.view', 'purchase-order.view', 'inward-entry.view',
                'packing.*',
                'report.view',
            ],
        ],

        'Quality Checker' => [
            'description' => 'Inspects inward goods and records pass/reject quantity.',
            'permissions' => [
                'product.view', 'supplier.view',
                'purchase-order.view',
                'inward-entry.view', 'inward-entry.edit',
                'quality-check.*',
                'debit-note.view', 'debit-note.create',
                'report.view',
            ],
        ],

        'Jobworker' => [
            'description' => 'External jobber. Sees only their own purchase orders and samples.',
            'permissions' => [
                'purchase-order.view',
                'sample-approval.view', 'sample-approval.create',
                'inward-entry.view', 'inward-entry.create',
            ],
        ],

    ],

];
