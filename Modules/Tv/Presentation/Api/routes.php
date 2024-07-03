<?php

use Illuminate\Http\Request;
use App\Http\Middleware\AuthIp;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Support\Facades\Route;
use \Modules\Tv\Presentation\Api\Controllers\HomeController;

Route::prefix('1.0')->group(function () {
    //Home content list
    Route::get('/tv/home/list/{section}',[HomeController::class,'getHomeContentList'])->middleware(AuthSanctum::class);
    Route::post('/tv/home/list/{section}',[HomeController::class,'setHomeContentList'])->middleware(AuthSanctum::class);
});
