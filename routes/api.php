<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SengPendataanKendaraanController;
use Illuminate\Support\Facades\Auth;

Route::post('/login', [AuthController::class, 'login']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/pendataan', [AuthController::class, 'profile']);
//     Route::post('/pendataan', [AuthController::class, 'logout']);
// });

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/pendataan', [AuthController::class, 'profile']);
//     Route::post('/pendataan', [AuthController::class, 'logout']);
// });


Route::middleware(['auth:sanctum', 'auth-api'])->group(function () {
    Route::apiResource('pendataan', SengPendataanKendaraanController::class);
});

