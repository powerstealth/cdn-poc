<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Modules\System\Presentation\Api\Controllers\SystemController;

Route::prefix('1.0/sys')->group(function () {
    Route::get('/ping',[SystemController::class,'ping']);
});
