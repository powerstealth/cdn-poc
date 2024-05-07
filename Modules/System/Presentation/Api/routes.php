<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('1.0/sys')->group(function () {
    Route::get('/ping', function () {
        return response()->json([]);
    });
});
