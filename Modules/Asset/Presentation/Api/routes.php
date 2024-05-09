<?php

use Illuminate\Http\Request;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Support\Facades\Route;
use \Modules\Asset\Presentation\Api\Controllers\AssetController;

Route::prefix('1.0')->group(function () {
    Route::post('/asset/upload',[AssetController::class,'getUploadSession'])->middleware(AuthSanctum::class);
});
