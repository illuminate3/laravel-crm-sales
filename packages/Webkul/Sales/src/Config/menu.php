<?php

return [
    /**
     * Sales.
     */
    [
        'key'        => 'sales',
        'name'       => 'sales::app.layouts.sales',
        'route'      => 'admin.sales.dashboard.index',
        'sort'       => 3,
        'icon-class' => 'icon-sales',
    ], [
        'key'        => 'sales.targets',
        'name'       => 'sales::app.layouts.targets',
        'route'      => 'admin.sales.targets.index',
        'sort'       => 1,
        'icon-class' => '',
    ], [
        'key'        => 'sales.performance',
        'name'       => 'sales::app.layouts.performance',
        'route'      => 'admin.sales.performance.index',
        'sort'       => 2,
        'icon-class' => '',
    ], [
        'key'        => 'sales.reports',
        'name'       => 'sales::app.layouts.reports',
        'route'      => 'admin.sales.reports.index',
        'sort'       => 3,
        'icon-class' => '',
    ],
];
