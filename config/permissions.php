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
        'sync'     => 'Sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules, grouped for the UI
    |--------------------------------------------------------------------------
    | key    => permission prefix, e.g. "product" => product.view, product.create
    | label  => shown in the permission matrix and the sidebar
    | actions=> optional override of default_actions
    | built  => false means the screen does not exist yet; it still gets a
    |           permission so roles can be configured ahead of the build,
    |           and the matrix shows it greyed out.
    */
    'groups' => [

        /*
         * The eight masters confirmed by the client. Nothing else belongs here —
         * supporting lookups (units, ports, currencies, HSN codes, sizes,
         * colours, payment terms, incoterms) are dropdown sources, not business
         * masters, and live on one Lookups screen under Settings.
         */
        'Masters' => [
            'supplier'  => ['label' => 'Supplier (Jobber)', 'built' => false],
            'buyer'     => ['label' => 'Buyer'],
            'agent'     => ['label' => 'Agent',             'built' => false],
            'product'   => ['label' => 'Product'],
            'category'  => ['label' => 'Category'],
            'po-format' => ['label' => 'PO Format',         'built' => false],
            'contract'  => ['label' => 'Contract',          'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'markup'    => ['label' => 'Markup',            'built' => false],
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
            'packing'            => ['label' => 'Packing',            'built' => false],
            'shipment'           => ['label' => 'Shipment',           'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'], 'built' => false],
            'export-document'    => ['label' => 'Export Documents',   'actions' => ['view', 'generate', 'export'], 'built' => false],
        ],

        'Accounts' => [
            'purchase-bill'    => ['label' => 'Purchase Bills',   'built' => false],
            'payment'          => ['label' => 'Payments',         'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'foreign-payment'  => ['label' => 'Foreign Payments', 'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'debit-note'       => ['label' => 'Debit Notes',      'actions' => ['view', 'create', 'edit', 'delete', 'approve'], 'built' => false],
            'agent-commission' => ['label' => 'Agent Commission', 'built' => false],
            'outstanding'      => ['label' => 'Outstanding',      'actions' => ['view', 'export'], 'built' => false],
        ],

        'Reports' => [
            'report' => ['label' => 'Reports', 'actions' => ['view', 'export'], 'built' => false],
        ],

        'Administration' => [
            'user'       => ['label' => 'Users'],
            'role'       => ['label' => 'Roles'],
            'permission' => ['label' => 'Permissions', 'actions' => ['view', 'sync']],
        ],

        'Settings' => [
            'lookup'        => ['label' => 'Lookups',         'built' => false],
            'company'       => ['label' => 'Company Profile', 'actions' => ['view', 'edit'], 'built' => false],
            'number-series' => ['label' => 'Number Series',   'actions' => ['view', 'edit'], 'built' => false],
            'activity-log'  => ['label' => 'Activity Logs',   'actions' => ['view'], 'built' => false],
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
                'supplier.*', 'buyer.*', 'agent.*', 'product.*', 'category.*',
                'po-format.*', 'contract.*', 'markup.*',
                'inquiry.*', 'quotation.*', 'order-confirmation.*', 'sample-approval.*',
                'purchase-order.*', 'material-issue.*', 'inward-entry.*', 'quality-check.*',
                'packing.*', 'shipment.*', 'export-document.*',
                'purchase-bill.*', 'payment.*', 'foreign-payment.*', 'debit-note.*',
                'agent-commission.*', 'outstanding.*', 'report.*',
                'lookup.*', 'company.*', 'number-series.*', 'activity-log.*',
            ],
        ],

        'Merchandising & Manufacturing' => [
            'description' => 'Handles inquiry to purchase order, suppliers and products.',
            'permissions' => [
                'supplier.*', 'product.*', 'category.view', 'agent.view',
                'buyer.view', 'contract.view', 'po-format.view', 'lookup.view',
                'inquiry.*', 'quotation.*', 'order-confirmation.*', 'sample-approval.*',
                'purchase-order.*', 'material-issue.*',
                'inward-entry.view', 'shipment.view',
                'report.view', 'report.export',
            ],
        ],

        'Accounts' => [
            'description' => 'Bills, payments, commission and outstanding.',
            'permissions' => [
                'supplier.view', 'buyer.view', 'agent.view', 'product.view',
                'markup.view', 'contract.view',
                'purchase-order.view', 'inward-entry.view', 'shipment.view',
                'purchase-bill.*', 'payment.*', 'foreign-payment.*', 'debit-note.*',
                'agent-commission.*', 'outstanding.*',
                'report.view', 'report.export',
            ],
        ],

        'Export Documentation & Foreign Payment' => [
            'description' => 'Shipment, export documents and foreign payment realisation.',
            'permissions' => [
                'buyer.view', 'product.view', 'category.view', 'contract.view', 'lookup.view',
                'order-confirmation.view', 'packing.view',
                'shipment.*', 'export-document.*',
                'foreign-payment.*',
                'report.view', 'report.export',
            ],
        ],

        'Packing' => [
            'description' => 'Packing lists, cartons and markings.',
            'permissions' => [
                'product.view', 'buyer.view', 'lookup.view',
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
