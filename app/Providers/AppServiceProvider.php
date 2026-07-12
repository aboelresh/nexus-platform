<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        JsonResource::withoutWrapping();

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}