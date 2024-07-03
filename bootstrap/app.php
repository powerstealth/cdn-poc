<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use \Modules\Asset\Presentation\Cli\Commands\PurgeUploads;
use \Modules\Asset\Presentation\Cli\Commands\S3Cors;
use \Modules\Auth\Presentation\Cli\Commands\SetUserRole;
use \Modules\Asset\Presentation\Cli\Commands\PurgeDeletedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function ($router) {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('Modules/System/Presentation/Api/routes.php'));
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('Modules/Auth/Presentation/Api/routes.php'));
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('Modules/Asset/Presentation/Api/routes.php'));
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('Modules/Tv/Presentation/Api/routes.php'));
        }
    )
    ->withCommands([
        PurgeUploads::class,
        S3Cors::class,
        SetUserRole::class,
        PurgeDeletedAssets::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        //$middleware->statefulApi();
        $middleware->convertEmptyStringsToNull(except: [
            fn ($request) => true,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })->create();
