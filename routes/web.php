<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\LogActivity;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifikasiController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\PelaporanController;



Route::middleware([LogActivity::class])->group(function () {
    Route::get('/your-route', [YourController::class, 'yourMethod']);
    Route::post('/another-route', [AnotherController::class, 'anotherMethod']);

    Route::prefix('dapur')->middleware('auth')->group(function () {
        Route::get('/dashboard', [BackController::class, 'index'])->name('dashboard');
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');

        Route::get('/users', [UserController::class, 'index'])->name('user.index');
        Route::get('/user/ganti', [UserController::class, 'ganti_password'])->name('user.ganti');

        Route::put('/user/{id}/update-password', [UserController::class, 'ganti_password_action'])->name('user.ganti_password');

        Route::post('/user/add', [UserController::class, 'store'])->name('user.add');
        Route::get('/user/get/{id}', [UserController::class, 'getAdmin'])->name('user.detail');
        Route::post('/user/update/{id}', [UserController::class, 'update'])->name('user.update');

        Route::delete('/user/delete/{id}', [UserController::class, 'softdelete'])->name('user.softdelete');

        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('/verifikasi-detail/{id}', [VerifikasiController::class, 'show'])->name('verifikasi-detail.index');
        Route::post('/verifikasi-status', [VerifikasiController::class, 'verif'])->name('verifikasi.status');

        Route::get('/download', [DownloadController::class, 'index'])->name('download.index');
        Route::get('/download-csv', [DownloadController::class, 'downloadCsv'])->name('download.csv');
        Route::get('/download-pdf', [DownloadController::class, 'downloadPdf'])->name('download.pdf');
        


        Route::get('/pelaporan', [PelaporanController::class, 'index'])->name('pelaporan.index');
        Route::get('/pelaporan-csv', [PelaporanController::class, 'pelaporanCsv'])->name('pelaporan.csv');
        Route::get('/pelaporan-pdf', [PelaporanController::class, 'pelaporanPdf'])->name('pelaporan.pdf');



        // Route::get('/downloads', [BackController::class, 'download']);
        // Route::get('/verifikasi', [BackController::class, 'verifikasi'])->name('verifikasi.index');
        // Route::get('/verifikasi-detail', [BackController::class, 'verifikasi_detail'])->name('verifikasi-detail.index');
        Route::get('/pelaporans', [BackController::class, 'pelaporan'])->name('pelaporan.indexs');

     


        Route::get('/get-districts', [WilayahController::class, 'getDistricts'])->name('getDistricts');

        
    });
});

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/act_login', [AuthController::class, 'login'])->name('login.action');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');





// Route::get('/', function () {
//     return view('welcome');
// });
