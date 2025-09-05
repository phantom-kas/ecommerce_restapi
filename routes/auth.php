<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;



Route::controller(AuthController::class)->group(function () {
  Route::post('/signup', 'register');
  Route::get('/sign', function () {
    return "<h1>Hello world signup</h1>";
  });

  Route::post('/login', 'login');
});
