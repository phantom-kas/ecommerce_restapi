<?php

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
    Route::post('/poduct/add', [ProductController::class, 'store']);
});


Route::middleware(['jwt.custom'])->group(function () {
    Route::get('/check_token', fn() =>  JsonResponseHelper::standardResponse(
            200,
            null,
            'token valid'
        ));
});

Route::get('/products', [ProductController::class, 'index']);
