<?php

use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SystemController::class, 'getSettings']);
Route::middleware(['jwt.custom', 'role:admin,super_admin'])->group(function () {
  Route::patch('/settings/ac', [SystemController::class, 'updateSettingsAllowCheckout']);
  Route::patch('/settings/ml', [SystemController::class, 'updateSettingsMelink']);
  Route::patch('/settings/cta', [SystemController::class, 'updateCta']);
  Route::patch('/settings/htxt', [SystemController::class, 'updateHerotxt']);
});
