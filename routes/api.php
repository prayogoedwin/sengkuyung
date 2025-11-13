<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SengPendataanKendaraanController;
use App\Http\Controllers\API\SengStatusController;
use App\Http\Controllers\API\SengStatusVerifikasiController;
use App\Http\Controllers\API\SengWilayahController;
use App\Http\Controllers\API\SengStatusFileController;
use App\Http\Controllers\API\RekapController;
use Illuminate\Support\Facades\Auth;

use App\Http\Middleware\LogActivity;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login_with_otp', [AuthController::class, 'login_otp']);
Route::post('/verifikasi_otp', [AuthController::class, 'verifyOtp']);


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
        // Route::apiResource('profil', [AuthController::class, 'show']);
        Route::apiResource('pendataan', SengPendataanKendaraanController::class);
        Route::post('pendataan/{id}/upload', [SengPendataanKendaraanController::class, 'upload']); // Rute khusus upload
        Route::get('/secure-file/{id}/{fileIndex}', [SengPendataanKendaraanController::class, 'getSecureFile']);
        Route::apiResource('status', SengStatusController::class);
        Route::apiResource('status-verifikasi', SengStatusVerifikasiController::class);
        Route::apiResource('wilayah', SengWilayahController::class);
        Route::apiResource('status-file', SengStatusFileController::class);
        Route::get('rekap', [RekapController::class, 'index']);
        Route::post('update_password', [AuthController::class, 'resetPassword']);
        
    });
 
});

   Route::get('rekap_download', [RekapController::class, 'rekapPreview']);
   Route::get('jurnal_download', [RekapController::class, 'jurnalPreview']);
   
   



