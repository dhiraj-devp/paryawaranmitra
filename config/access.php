<?php

$modules = [
    'notifications' => 'Notifications and reminders',
    'enquiries' => 'Enquiries',
    'clients' => 'Clients',
    'work-orders' => 'Jobs and work orders',
    'services' => 'Services',
    'fees' => 'Fees',
    'documents' => 'Documents',
    'applications' => 'Applications',
    'notices' => 'Notices and queries',
    'quotations' => 'Quotations',
    'invoices' => 'Invoices',
    'payments' => 'Payments and receipts',
    'expenses' => 'Expenses',
    'revenue' => 'Revenue and profit',
    'compliance' => 'Compliance',
    'authorizations' => 'Authorizations and returns',
    'bank-guarantees' => 'Bank guarantees',
    'treatment-plants' => 'ETP, STP and APCS',
    'site-visits' => 'Site visits',
    'tasks' => 'Tasks and staff',
    'communications' => 'WhatsApp and email',
    'reports' => 'Reports',
    'automation' => 'Automation',
    'users' => 'Users and permissions',
    'settings' => 'Settings',
    'audit' => 'Audit logs',
];

$permissions = [];

foreach ($modules as $module => $label) {
    $permissions[] = [
        'slug' => $module.'.view',
        'module' => $module,
        'name' => 'View '.$label,
    ];
    $permissions[] = [
        'slug' => $module.'.manage',
        'module' => $module,
        'name' => 'Manage '.$label,
    ];
}

foreach ([
    'view' => 'View follow-ups',
    'create' => 'Create follow-ups',
    'edit' => 'Edit follow-ups',
    'delete' => 'Delete follow-ups',
    'complete' => 'Complete follow-ups',
] as $ability => $name) {
    $permissions[] = [
        'slug' => 'follow-ups.'.$ability,
        'module' => 'follow-ups',
        'name' => $name,
    ];
}

$followUpManage = [
    'follow-ups.view',
    'follow-ups.create',
    'follow-ups.edit',
    'follow-ups.delete',
    'follow-ups.complete',
];

$grant = function (array $manage, array $view): array {
    $slugs = [];

    foreach ($manage as $module) {
        $slugs[] = $module.'.view';
        $slugs[] = $module.'.manage';
    }

    foreach ($view as $module) {
        $slugs[] = $module.'.view';
    }

    return array_values(array_unique($slugs));
};

return [

    'permissions' => $permissions,

    'roles' => [
        'super-admin' => [
            'name' => 'Super Admin',
            'portal' => 'admin',
            'description' => 'Full access to organisation administration.',
            'permissions' => '*',
        ],
        'office-manager' => [
            'name' => 'Office Manager',
            'portal' => 'staff',
            'description' => 'Runs daily client work and reviews financial activity.',
            'permissions' => array_values(array_unique(array_merge($grant(
                ['notifications', 'enquiries', 'clients', 'work-orders', 'services', 'fees', 'documents', 'applications', 'notices', 'communications', 'reports', 'tasks', 'site-visits', 'compliance'],
                ['quotations', 'invoices', 'payments', 'expenses', 'revenue', 'authorizations', 'bank-guarantees', 'treatment-plants'],
            ), $followUpManage))),
        ],
        'accounts' => [
            'name' => 'Accounts',
            'portal' => 'staff',
            'description' => 'Records quotations, invoices, receipts and expenses.',
            'permissions' => array_values(array_unique(array_merge($grant(
                ['quotations', 'invoices', 'payments', 'expenses', 'revenue'],
                ['clients', 'work-orders', 'enquiries', 'reports', 'notifications'],
            ), ['follow-ups.view']))),
        ],
        'consultant' => [
            'name' => 'Consultant',
            'portal' => 'staff',
            'description' => 'Prepares applications, compliance and site work.',
            'permissions' => array_values(array_unique(array_merge($grant(
                ['enquiries', 'work-orders', 'documents', 'applications', 'notices', 'compliance', 'authorizations', 'bank-guarantees', 'treatment-plants', 'site-visits', 'tasks'],
                ['clients', 'services', 'fees', 'notifications', 'reports'],
            ), $followUpManage))),
        ],
        'office-executive' => [
            'name' => 'Office Executive',
            'portal' => 'staff',
            'description' => 'Receives enquiries and collects client documents.',
            'permissions' => array_values(array_unique(array_merge($grant(
                ['enquiries', 'clients', 'documents', 'tasks', 'notifications'],
                ['work-orders', 'services', 'fees', 'communications'],
            ), $followUpManage))),
        ],
    ],

];
