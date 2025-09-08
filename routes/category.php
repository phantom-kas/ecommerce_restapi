<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::post('/category', [CategoryController::class, 'store'])->middleware('jwt.custom');

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/short', [CategoryController::class, 'short']);