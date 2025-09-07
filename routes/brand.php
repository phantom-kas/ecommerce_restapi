<?php

use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

Route::post('/brand', [BrandController::class, 'store'])->middleware('jwt.custom');
