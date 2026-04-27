<?php

namespace App\Providers;

use App\Services\Contracts\ProjectServiceInterface;
use App\Services\OpenStack\OpenStackClient;
use App\Services\ProjectService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);

        $this->app->singleton(OpenStackClient::class, fn ($app) => new OpenStackClient(
            (string) $app['config']->get('services.openstack.auth_url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
