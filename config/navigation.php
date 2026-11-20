<?php

return [

    'groups' => [
        [
            'key' => 'main',
            'items' => [
                ['key' => 'dashboard', 'icon' => 'home', 'route' => 'dashboard', 'permission' => null],
                ['key' => 'notifications', 'icon' => 'bell', 'route' => null, 'permission' => 'notifications.view'],
            ],
        ],
        [
            'key' => 'customers',
            'items' => [
                ['key' => 'enquiries', 'icon' => 'inbox', 'route' => 'enquiries.index', 'active' => 'enquiries.*', 'permission' => 'enquiries.view'],
                ['key' => 'follow_ups', 'icon' => 'check', 'route' => 'follow-ups.index', 'active' => 'follow-ups.*', 'permission' => 'follow-ups.view'],
                ['key' => 'clients', 'icon' => 'users', 'route' => 'clients.index', 'active' => 'clients.*', 'permission' => 'clients.view'],
                ['key' => 'work_orders', 'icon' => 'clipboard', 'route' => 'work-orders.index', 'active' => 'work-orders.*', 'permission' => 'work-orders.view'],
            ],
        ],
        [
            'key' => 'services',
            'items' => [
                ['key' => 'services', 'icon' => 'layers', 'route' => 'services.index', 'active' => 'services.*', 'permission' => 'services.view'],
                ['key' => 'fees', 'icon' => 'banknotes', 'route' => 'fees.index', 'active' => 'fees.*', 'permission' => 'fees.view'],
                ['key' => 'documents', 'icon' => 'folder', 'route' => null, 'permission' => 'documents.view'],
                ['key' => 'applications', 'icon' => 'file', 'route' => null, 'permission' => 'applications.view'],
                ['key' => 'notices', 'icon' => 'megaphone', 'route' => null, 'permission' => 'notices.view'],
            ],
        ],
        [
            'key' => 'finance',
            'items' => [
                ['key' => 'quotations', 'icon' => 'document', 'route' => 'quotations.index', 'active' => 'quotations.*', 'permission' => 'quotations.view'],
                ['key' => 'invoices', 'icon' => 'receipt', 'route' => null, 'permission' => 'invoices.view'],
                ['key' => 'payments', 'icon' => 'card', 'route' => null, 'permission' => 'payments.view'],
                ['key' => 'expenses', 'icon' => 'wallet', 'route' => null, 'permission' => 'expenses.view'],
                ['key' => 'revenue', 'icon' => 'chart', 'route' => null, 'permission' => 'revenue.view'],
            ],
        ],
        [
            'key' => 'operations',
            'items' => [
                ['key' => 'compliance', 'icon' => 'shield', 'route' => null, 'permission' => 'compliance.view'],
                ['key' => 'authorizations', 'icon' => 'stamp', 'route' => null, 'permission' => 'authorizations.view'],
                ['key' => 'bank_guarantees', 'icon' => 'landmark', 'route' => null, 'permission' => 'bank-guarantees.view'],
                ['key' => 'treatment_plants', 'icon' => 'droplet', 'route' => null, 'permission' => 'treatment-plants.view'],
                ['key' => 'site_visits', 'icon' => 'pin', 'route' => null, 'permission' => 'site-visits.view'],
                ['key' => 'tasks', 'icon' => 'check', 'route' => null, 'permission' => 'tasks.view'],
            ],
        ],
        [
            'key' => 'communication',
            'items' => [
                ['key' => 'communications', 'icon' => 'chat', 'route' => null, 'permission' => 'communications.view'],
                ['key' => 'reports', 'icon' => 'presentation', 'route' => null, 'permission' => 'reports.view'],
            ],
        ],
        [
            'key' => 'system',
            'items' => [
                ['key' => 'automation', 'icon' => 'bolt', 'route' => null, 'permission' => 'automation.view'],
                ['key' => 'users', 'icon' => 'key', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view'],
                ['key' => 'settings', 'icon' => 'settings', 'route' => 'settings.index', 'active' => 'settings.*', 'permission' => 'settings.view'],
            ],
        ],
    ],

    /*
    | Modules register create actions here once the destination exists.
    | Each action: label, route (without the portal prefix), permission, portals.
    */
    'quick_actions' => [
        [
            'label' => 'ui.enquiries.create',
            'route' => 'enquiries.create',
            'icon' => 'clipboard',
            'permission' => 'enquiries.manage',
            'portals' => ['admin', 'staff'],
        ],
        [
            'label' => 'ui.clients.create',
            'route' => 'clients.create',
            'icon' => 'users',
            'permission' => 'clients.manage',
            'portals' => ['admin', 'staff'],
        ],
        [
            'label' => 'ui.services.create',
            'route' => 'services.create',
            'icon' => 'layers',
            'permission' => 'services.manage',
            'portals' => ['admin', 'staff'],
        ],
        [
            'label' => 'ui.fees.create',
            'route' => 'fees.create',
            'icon' => 'banknotes',
            'permission' => 'fees.manage',
            'portals' => ['admin', 'staff'],
        ],
        [
            'label' => 'ui.quotations.create',
            'route' => 'quotations.create',
            'icon' => 'file',
            'permission' => 'quotations.manage',
            'portals' => ['admin', 'staff'],
        ],
    ],

];
