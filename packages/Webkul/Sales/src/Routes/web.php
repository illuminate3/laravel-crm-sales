<?php

use Illuminate\Support\Facades\Route;
use Webkul\Sales\Http\Controllers\DashboardController;
use Webkul\Sales\Http\Controllers\TargetController;
use Webkul\Sales\Http\Controllers\PerformanceController;
use Webkul\Sales\Http\Controllers\ReportController;

/**
 * Sales routes.
 */
Route::prefix('sales')->name('admin.sales.')->group(function () {
    /**
     * Dashboard routes.
     */
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    /**
     * Target routes.
     */
    Route::controller(TargetController::class)->prefix('targets')->name('targets.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('', 'store')->name('store');
        Route::get('{id}/edit', 'edit')->name('edit');
        Route::put('{id}', 'update')->name('update');
        Route::delete('{id}', 'destroy')->name('delete');
        Route::post('mass-update', 'massUpdate')->name('mass_update');
        Route::post('mass-delete', 'massDestroy')->name('mass_delete');
    });

    /**
     * Performance routes.
     */
    Route::controller(PerformanceController::class)->prefix('performance')->name('performance.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('stats', 'stats')->name('stats');
        Route::get('leaderboard', 'leaderboard')->name('leaderboard');
        Route::post('switch-view', 'switchView')->name('switch_view');
        Route::get('{id}', 'view')->name('view');
    });

    /**
     * Report routes.
     */
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('', 'store')->name('store');
        Route::get('{id}', 'view')->name('view');
        Route::get('{id}/export', 'export')->name('export');
        Route::delete('{id}', 'destroy')->name('delete');
        Route::post('mass-delete', 'massDestroy')->name('mass_delete');
    });
});
