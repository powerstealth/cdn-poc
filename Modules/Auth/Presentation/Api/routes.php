<?php

use App\Http\Middleware\Sso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Modules\Auth\Presentation\Api\Controllers\AuthController;

Route::prefix('1.0/auth')->middleware(Sso::class)->group(function () {
    Route::get('/sso',[AuthController::class,'sso']);
});
