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
    //Upload a poster
    Route::post('/asset/{id}/poster',[AssetController::class,'uploadPosterToAsset'])->middleware(AuthSanctum::class);
    //Delete an asset (soft)
    Route::delete('/asset/{id}',[AssetController::class,'softDeleteAsset'])->middleware(AuthSanctum::class);
    //Delete an asset (hard)
    Route::delete('/asset/{id}/hard',[AssetController::class,'hardDeleteAsset'])
        ->middleware(AuthSanctum::class,'role:admin');
    //Stream an asset
    Route::get('/asset/{id}/stream/{json?}',[AssetController::class,'streamAsset']);
    //Download an original asset
    Route::get('/asset/{id}/download',[AssetController::class,'downloadOriginalAsset'])
        ->middleware(AuthSanctum::class);
    //Download the frames
    Route::get('/asset/{id}/download/frames',[AssetController::class,'downloadAssetFrames'])
        ->middleware(AuthSanctum::class);
    //Show asset's categories list
    Route::get('/tag-groups',[AssetController::class,'getTagGroups'])
        ->middleware(AuthSanctum::class,'role:admin');
});
