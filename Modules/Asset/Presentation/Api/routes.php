<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('1.0')->group(function () {
    Route::get('/sys/ping', function () {
        return response()->json([]);
    });
});
