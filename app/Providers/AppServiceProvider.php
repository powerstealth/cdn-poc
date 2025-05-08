<?php

namespace App\Providers;

use Modules\Asset\Domain\Models\Asset;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Modules\Asset\Domain\Observers\AssetObserver;

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
        // Loader Alias
        $loader = AliasLoader::getInstance();

        // Sanctum custom personal access token Alias
        $loader->alias(
            \Laravel\Sanctum\PersonalAccessToken::class,
            \Modules\Auth\Domain\Models\PersonalAccessToken::class
        );

        // Observers
        Asset::observe(AssetObserver::class);
    }
}
