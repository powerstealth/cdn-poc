<?php

namespace App\Providers;

use Illuminate\Foundation\AliasLoader;
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
        //Loader Alias
        $loader = AliasLoader::getInstance();

        //Sanctum custom personal access token
        $loader->alias(\Laravel\Sanctum\PersonalAccessToken::class, \Modules\Auth\Domain\Models\PersonalAccessToken::class);
    }
}
