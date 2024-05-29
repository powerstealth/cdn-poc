<?php

use Illuminate\Http\Request;
use App\Http\Middleware\AuthSanctum;
use Illuminate\Support\Facades\Route;
use \Modules\Asset\Presentation\Api\Controllers\AssetController;

Route::prefix('1.0')->group(function () {
    //Single Upload
    //Route::post('/asset/upload',[AssetController::class,'setUploadSession'])->middleware(AuthSanctum::class);
    //Multipart upload
    Route::post('/asset/upload/multipart',[AssetController::class,'multipartUpload'])->middleware(AuthSanctum::class);
    //List
    Route::post('/assets',[AssetController::class,'getAssets'])->middleware(AuthSanctum::class);
    //Get an asset
    Route::get('/asset/{id}',[AssetController::class,'getAsset'])->middleware(AuthSanctum::class);
    //Update an asset
    Route::put('/asset/{id}',[AssetController::class,'updateAsset'])->middleware(AuthSanctum::class);
    //Delete an asset
    Route::delete('/asset/{id}',[AssetController::class,'deleteAsset'])->middleware(AuthSanctum::class);
    //Stream an asset
    Route::get('/asset/stream/{id}',[AssetController::class,'streamAsset']);
});
