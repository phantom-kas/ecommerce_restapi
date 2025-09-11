<?php

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
    Route::post('/product/add', [ProductController::class, 'store']);
    Route::post('/product/addMedia/{id}', [ProductController::class, 'addMedia']);
    Route::delete('/product/{id}/media/delete', [ProductController::class, 'deleteMedia']);
    Route::delete('/product/{id}/delete', [ProductController::class, 'delete']);
    Route::delete('/product/{id}/restore', [ProductController::class, 'restoreDeleted']);
});



Route::middleware(['jwt.custom'])->group(function () {
    Route::get('/check_token', fn() =>  JsonResponseHelper::standardResponse(
        200,
        null,
        'token valid'
    ));
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}/media', [ProductController::class, 'getMedia']);
