<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom'])->group(function () {

  
  Route::post('/initpay', [PaymentController::class, 'initializeTransaction']);
  Route::get('/paystack-callback', [PaymentController::class, 'paystackCallBack']);
});
