<?php

namespace App\Providers;

use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Eloquent\ProjectRepository;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\ProjectService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ProjectServiceInterface::class, ProjectService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
