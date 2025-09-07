<?php

use App\Helpers\JsonResponseHelper;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
    Route::post('/poduct/add', fn() => response()->json(['msg' => 'Admins and Super Admins only']));
});


Route::middleware(['jwt.custom'])->group(function () {
    Route::get('/check_token', fn() =>  JsonResponseHelper::standardResponse(
            200,
            null,
            'token valid'
        ));
});