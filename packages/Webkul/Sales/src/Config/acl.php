<?php

return [
    [
        'key'   => 'sales',
        'name'  => 'sales::app.acl.sales',
        'route' => 'admin.sales.dashboard.index',
        'sort'  => 3,
    ], [
        'key'   => 'sales.targets',
        'name'  => 'sales::app.acl.targets',
        'route' => 'admin.sales.targets.index',
        'sort'  => 1,
    ], [
        'key'   => 'sales.targets.create',
        'name'  => 'sales::app.acl.create',
        'route' => ['admin.sales.targets.create', 'admin.sales.targets.store'],
        'sort'  => 1,
    ], [
        'key'   => 'sales.targets.edit',
        'name'  => 'sales::app.acl.edit',
        'route' => ['admin.sales.targets.edit', 'admin.sales.targets.update'],
        'sort'  => 2,
    ], [
        'key'   => 'sales.targets.delete',
        'name'  => 'sales::app.acl.delete',
        'route' => 'admin.sales.targets.delete',
        'sort'  => 3,
    ], [
        'key'   => 'sales.performance',
        'name'  => 'sales::app.acl.performance',
        'route' => 'admin.sales.performance.index',
        'sort'  => 2,
    ], [
        'key'   => 'sales.performance.view',
        'name'  => 'sales::app.acl.view',
        'route' => 'admin.sales.performance.view',
        'sort'  => 1,
    ], [
        'key'   => 'sales.reports',
        'name'  => 'sales::app.acl.reports',
        'route' => 'admin.sales.reports.index',
        'sort'  => 3,
    ], [
        'key'   => 'sales.reports.create',
        'name'  => 'sales::app.acl.create',
        'route' => ['admin.sales.reports.create', 'admin.sales.reports.store'],
        'sort'  => 1,
    ], [
        'key'   => 'sales.reports.export',
        'name'  => 'sales::app.acl.export',
        'route' => 'admin.sales.reports.export',
        'sort'  => 2,
    ],
];
