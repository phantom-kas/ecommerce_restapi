<?php

use App\Http\Controllers\ReviewsContoller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom'])->group(function () {
  Route::post('/create-review', [ReviewsContoller::class, 'store']);
});

Route::get('/review/product-reviews/{id}', [ReviewsContoller::class, 'getProductReviews']);


Route::get('/run-migrations', function () {
    Artisan::call('migrate --force');
    return 'Migrations completed!';
});