<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;




Route::middleware(['jwt.custom'])->group(function () {
  Route::post('/cart/add-item', [CartController::class, 'addItemToCart']);
  Route::get('/cart', [CartController::class, 'getCartActive']);
  Route::delete('/cart/remove-item', [CartController::class, 'removeProductFromCart']);
  Route::post('/cart/update-item-count', [CartController::class, 'setCartItemQuantity']);
  Route::get('/orders', [CartController::class, 'getAllOrders']);
  Route::get('/order/items/{id}', [CartController::class, 'getOrderItems']);
});
