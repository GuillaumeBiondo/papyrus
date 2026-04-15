<?php

namespace App\Providers;

use App\Models\Scene;
use App\Observers\SceneObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Scene::observe(SceneObserver::class);
    }
}
