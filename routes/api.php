<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SengPendataanKendaraanController;
use App\Http\Controllers\API\SengPendataanKendaraanD2dController;
use App\Http\Controllers\API\SengStatusController;
use App\Http\Controllers\API\SengStatusVerifikasiController;
use App\Http\Controllers\API\SengWilayahController;
use App\Http\Controllers\API\SengStatusFileController;
use App\Http\Controllers\API\RekapController;
use App\Http\Controllers\API\RekapD2dController;
use App\Http\Controllers\API\DataTertagihController;
use App\Http\Controllers\API\DataTertagihD2dController;
use App\Http\Controllers\API\AlasanTidakBayarPajakController;
use App\Http\Controllers\KebijakanPrivasiController;
use Illuminate\Support\Facades\Auth;

use App\Http\Middleware\LogActivity;

Route::get('/kebijakan-privasi', [KebijakanPrivasiController::class, 'api']);

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
        Route::post('pendataan/{id}/upload', [SengPendataanKendaraanController::class, 'upload']);
        Route::get('/secure-file/{id}/{fileIndex}', [SengPendataanKendaraanController::class, 'getSecureFile']);
        Route::middleware('petugas-d2d.api')->group(function () {
            Route::apiResource('pendataan-d2d', SengPendataanKendaraanD2dController::class);
            Route::post('pendataan-d2d/{id}/upload', [SengPendataanKendaraanD2dController::class, 'upload']);
            Route::get('/secure-file-d2d/{id}/{fileIndex}', [SengPendataanKendaraanD2dController::class, 'getSecureFile']);
        });
        Route::apiResource('status', SengStatusController::class);
        Route::apiResource('status-verifikasi', SengStatusVerifikasiController::class);
        Route::apiResource('wilayah', SengWilayahController::class);
        Route::apiResource('status-file', SengStatusFileController::class);
        Route::get('alasan-tidak-bayar-pajak', [AlasanTidakBayarPajakController::class, 'index']);
        Route::get('rekap', [RekapController::class, 'index']);
        Route::middleware('petugas-d2d.api')->group(function () {
            Route::get('rekap-d2d', [RekapD2dController::class, 'index']);
        });
        Route::post('update_password', [AuthController::class, 'resetPassword']);
        Route::middleware('petugas.api')->group(function () {
            Route::post('data-tertagih/list', [DataTertagihController::class, 'index']);
            Route::get('data-tertagih/{id}', [DataTertagihController::class, 'show']);
        });
        Route::middleware('petugas-d2d.api')->group(function () {
            Route::post('data-tertagih-d2d/list', [DataTertagihD2dController::class, 'index']);
            Route::get('data-tertagih-d2d/{id}', [DataTertagihD2dController::class, 'show']);
        });
        
    });
 
});

   Route::get('rekap_download', [RekapController::class, 'rekapPreview']);
   Route::get('jurnal_download', [RekapController::class, 'jurnalPreview']);
   Route::get('rekap_download_d2d', [RekapD2dController::class, 'rekapPreview']);
   Route::get('jurnal_download_d2d', [RekapD2dController::class, 'jurnalPreview']);
   
   



