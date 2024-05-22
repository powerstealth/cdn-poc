<?php

return [
    App\Providers\AppServiceProvider::class,
    MongoDB\Laravel\MongoDBServiceProvider::class,
    ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    Mehrdadakbari\Mongodb\Permissions\PermissionServiceProvider::class,
];
