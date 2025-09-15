<?php

use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;


Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
Route::post('/brand', [BrandController::class, 'store'])->middleware('jwt.custom');
});
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/short', [BrandController::class, 'short']);