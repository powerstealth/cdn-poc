<?php

use App\Http\Middleware\Sso;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Modules\Auth\Presentation\Api\Controllers\AuthController;

Route::prefix('1.0/auth')->group(function () {
    Route::get('/sso',[AuthController::class,'sso'])->middleware(Sso::class);
    Route::get('/user',[AuthController::class,'userInfo'])->middleware(AuthSanctum::class);
});
