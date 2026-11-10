<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Week 11 - Step 4.2: throw an exception whenever a relationship is
        // lazy-loaded in development, so the N+1 problem is loud and visible.
        Model::preventLazyLoading(! app()->isProduction());
    }
}
