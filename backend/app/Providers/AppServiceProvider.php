<?php

namespace App\Providers;

use App\Auth\ShibbolethGuard;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerActionServiceInterface;
use App\Services\Contracts\ServerControlServiceInterface;
use App\Services\InventoryService;
use App\Services\OpenStack\OpenStackClient;
use App\Services\ProjectService;
use App\Services\ServerActionService;
use App\Services\ServerControlService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
        $this->app->bind(ServerActionServiceInterface::class, ServerActionService::class);
        $this->app->bind(OpenStackClientInterface::class, OpenStackClient::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(ServerControlServiceInterface::class, ServerControlService::class);

        $this->app->singleton(OpenStackClient::class, fn ($app) => new OpenStackClient(
            (string) $app['config']->get('services.openstack.auth_url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('shibboleth', fn () => new ShibbolethGuard);
    }
}
