<?php

namespace Webkul\Sales\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Observers\SalesPerformanceObserver;
use Webkul\Sales\Observers\SalesTargetObserver;
use Webkul\Sales\Console\Commands\RecalculateSalesPerformanceCommand;
use Webkul\Sales\Console\Commands\MigrateSalesDataCommand;
use Webkul\Sales\Console\Commands\ValidateSalesDataCommand;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'sales');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'sales');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/web.php');

        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('vendor/sales'),
        ], 'sales-assets');

        $this->registerEventListeners();
        $this->registerObservers();
        $this->registerCommands();
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRepositories();
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/breadcrumbs.php', 'breadcrumbs');
    }

    /**
     * Register repositories.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(
            \Webkul\Sales\Repositories\SalesTargetRepository::class
        );

        $this->app->bind(
            \Webkul\Sales\Repositories\SalesPerformanceRepository::class
        );

        $this->app->bind(
            \Webkul\Sales\Repositories\SalesReportRepository::class
        );

        $this->app->bind(
            \Webkul\Sales\Services\SalesPerformanceCalculationService::class
        );
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Listen for lead events to update target progress
        Event::listen('lead.update.after', function ($lead) {
            app(\Webkul\Sales\Listeners\LeadListener::class)->handleLeadUpdate($lead);
        });

        Event::listen('lead.create.after', function ($lead) {
            app(\Webkul\Sales\Listeners\LeadListener::class)->handleLeadCreate($lead);
        });

        Event::listen('lead.delete.before', function ($lead) {
            app(\Webkul\Sales\Listeners\LeadListener::class)->handleLeadDelete($lead);
        });
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        SalesPerformance::observe(SalesPerformanceObserver::class);
        SalesTarget::observe(SalesTargetObserver::class);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RecalculateSalesPerformanceCommand::class,
                MigrateSalesDataCommand::class,
                ValidateSalesDataCommand::class,
            ]);
        }
    }
}
