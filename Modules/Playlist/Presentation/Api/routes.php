<?php

use Illuminate\Http\Request;
use App\Http\Middleware\AuthIp;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Support\Facades\Route;
use \Modules\Playlist\Presentation\Api\Controllers\PlaylistController;

Route::prefix('1.0')->group(function () {
    //Get Virtual Show Playlist
    Route::get('/playlist/virtual-show',[PlaylistController::class,'getVirtualShowPlaylist'])
        ->middleware(AuthSanctum::class);
    //Set Virtual Show Playlist
    Route::post('/playlist/virtual-show',[PlaylistController::class,'setVirtualShowPlaylist'])
        ->middleware(AuthSanctum::class);
    //Stream a Virtual Show Playlist
    Route::middleware(AuthIp::class)
        ->prefix('/playlist/virtual-show')
        ->group(function () {
            Route::get('/stream',[PlaylistController::class,'streamVirtualShowPlaylist']);
            Route::get('/{user}/stream',[PlaylistController::class,'streamVirtualShowPlaylist']);
        });
    //Get a playlist
    Route::get('/playlist/{section}',[PlaylistController::class,'getPlaylist'])
        ->middleware(AuthSanctum::class,'role:admin');
    //Set a playlist
    Route::post('/playlist/{section}',[PlaylistController::class,'setPlaylist'])
        ->middleware(AuthSanctum::class,'role:admin');
    //Stream a playlist
    Route::get('/playlist/{section}/stream',[PlaylistController::class,'streamPlaylist'])
        ->middleware(AuthIp::class);
});
