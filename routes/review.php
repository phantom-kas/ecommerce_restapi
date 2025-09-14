<?php

use App\Http\Controllers\ReviewsContoller;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
  Route::post('/create-review', [ReviewsContoller::class, 'store']);
});

Route::get('/review/product-reviews/{id}', [ReviewsContoller::class, 'getProductReviews']);