<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SengPendataanKendaraanController;
use App\Http\Controllers\API\SengStatusController;
use App\Http\Controllers\API\SengStatusVerifikasiController;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\LogActivity;

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


// Route::middleware([ 'auth-api'])->group(function () {
//     Route::apiResource('pendataan', SengPendataanKendaraanController::class);
//     Route::apiResource('status', SengStatusController::class);
//     Route::apiResource('status-verifikasi', SengStatusVerifikasiController::class);
// });

Route::middleware(['auth-api'])->group(function () {
    Route::middleware([LogActivity::class])->group(function () {
        Route::apiResource('pendataan', SengPendataanKendaraanController::class);
        Route::apiResource('status', SengStatusController::class);
        Route::apiResource('status-verifikasi', SengStatusVerifikasiController::class);
    });
});

