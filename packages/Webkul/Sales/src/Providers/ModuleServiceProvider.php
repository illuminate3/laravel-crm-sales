<?php

namespace Webkul\Sales\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Sales\Models\SalesTarget::class,
        \Webkul\Sales\Models\SalesPerformance::class,
        \Webkul\Sales\Models\SalesReport::class,
    ];
}
