<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/test_api', function () {
    return "oposadsamdsam";
});

Route::get('/', function () {
    return "<h1>Hello world</h1>";
});





require __DIR__ . '/auth.php';
require __DIR__ . '/products.php';
require __DIR__ . '/brand.php';
require __DIR__ . '/category.php';


