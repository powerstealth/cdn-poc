<?php

use App\Http\Middleware\Sso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('1.0/auth')->middleware(Sso::class)->group(function () {
    Route::get('/sso/{token}', function () {
        return response()->json([]);
    });
});
