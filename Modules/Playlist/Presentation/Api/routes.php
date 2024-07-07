<?php

use Illuminate\Http\Request;
use App\Http\Middleware\AuthIp;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Support\Facades\Route;
use \Modules\Playlist\Presentation\Api\Controllers\PlaylistController;

Route::prefix('1.0')->group(function () {
    //Home content list
    Route::get('/playlist/{section}',[PlaylistController::class,'getPlaylist'])->middleware(AuthSanctum::class)->middleware(AuthSanctum::class);
    Route::get('/playlist/{section}/stream',[PlaylistController::class,'streamPlaylist']);
    Route::post('/playlist/{section}',[PlaylistController::class,'setPlaylist'])->middleware(AuthSanctum::class)->middleware(AuthSanctum::class);
});
