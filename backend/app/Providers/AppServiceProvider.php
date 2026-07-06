<?php

namespace App\Providers;

use App\Auth\ShibbolethGuard;
use App\Models\Setting;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\RegionServiceInterface;
use App\Services\Contracts\ServerActionServiceInterface;
use App\Services\Contracts\ServerControlServiceInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use App\Services\InventoryService;
use App\Services\OpenStack\OpenStackClient;
use App\Services\PendingActionTracker;
use App\Services\ProjectService;
use App\Services\RegionService;
use App\Services\ServerActionService;
use App\Services\ServerControlService;
use App\Services\ServerStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
        $this->app->bind(RegionServiceInterface::class, RegionService::class);
        $this->app->bind(ServerActionServiceInterface::class, ServerActionService::class);
        $this->app->bind(OpenStackClientInterface::class, OpenStackClient::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(ServerControlServiceInterface::class, ServerControlService::class);
        $this->app->bind(ServerStatusServiceInterface::class, ServerStatusService::class);
        $this->app->bind(PendingActionTrackerInterface::class, PendingActionTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('shibboleth', fn () => new ShibbolethGuard);

        View::composer('layouts.app', function ($view): void {
            $tableReady = Schema::hasTable('settings');

            $view->with('inventoryIntervalMinutes', $tableReady
                ? Setting::inventoryIntervalMinutes()
                : Setting::DEFAULT_INVENTORY_INTERVAL_MINUTES);
            $view->with('allowedInventoryIntervals', Setting::ALLOWED_INVENTORY_INTERVAL_MINUTES);
        });
    }
}
