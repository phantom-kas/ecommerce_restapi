<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;




Route::middleware(['jwt.custom'])->group(function () {
  Route::post('/cart/add-item', [CartController::class, 'addItemToCart']);
  Route::get('/cart', [CartController::class, 'getCartActive']);
});