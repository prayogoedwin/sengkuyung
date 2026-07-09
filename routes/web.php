<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\LogActivity;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifikasiController;
use App\Http\Controllers\VerifikasiD2dController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\RekapD2dController;
use App\Http\Controllers\PelaporanController;
use App\Http\Controllers\PelaporanD2dController;
use App\Http\Controllers\PerbandinganKodeWilayahController;
use App\Http\Controllers\DataTertagihController;
use App\Http\Controllers\DataTertagihD2dController;
use App\Http\Controllers\CacheManagementController;
use App\Http\Controllers\KebijakanPrivasiController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\JasaRaharjaController;
use App\Http\Controllers\MaintenanceStatusController;

Route::get('/kebijakan-privasi', [KebijakanPrivasiController::class, 'show'])->name('kebijakan-privasi');

Route::middleware([LogActivity::class])->group(function () {
    Route::prefix('dapur')->middleware(['auth', 'deny.petugas.web'])->group(function () {
        Route::get('/dashboard', [BackController::class, 'index'])->name('dashboard');
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap-d2d', [RekapD2dController::class, 'index'])->name('rekap-d2d.index');

        Route::get('/users', [UserController::class, 'index'])->name('user.index');
        Route::get('/user/ganti', [UserController::class, 'ganti_password'])->name('user.ganti');

        Route::put('/user/{id}/update-password', [UserController::class, 'ganti_password_action'])->name('user.ganti_password');

        Route::post('/user/add', [UserController::class, 'store'])->name('user.add');
        Route::get('/user/get/{id}', [UserController::class, 'getAdmin'])->name('user.detail');
        Route::post('/user/update/{id}', [UserController::class, 'update'])->name('user.update');
        Route::put('/user/reset-password/{id}', [UserController::class, 'resetPasswordToEmail'])->name('user.reset-password');

        Route::delete('/user/delete/{id}', [UserController::class, 'softdelete'])->name('user.softdelete');

        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('/verifikasi-detail/{id}', [VerifikasiController::class, 'show'])->name('verifikasi-detail.index');
        Route::post('/verifikasi-status/{id}', [VerifikasiController::class, 'verif'])->name('verifikasi.status');

        Route::get('/verifikasi-d2d', [VerifikasiD2dController::class, 'index'])->name('verifikasi-d2d.index');
        Route::get('/verifikasi-d2d-detail/{id}', [VerifikasiD2dController::class, 'show'])->name('verifikasi-d2d-detail.index');
        Route::post('/verifikasi-d2d-status/{id}', [VerifikasiD2dController::class, 'verif'])->name('verifikasi-d2d.status');

        Route::get('/download', [DownloadController::class, 'index'])->name('download.index');
        Route::get('/download-csv', [DownloadController::class, 'downloadCsv'])->name('download.csv');
        Route::get('/download-pdf', [DownloadController::class, 'downloadPdf'])->name('download.pdf');

        Route::get('/pelaporan', [PelaporanController::class, 'index'])->name('pelaporan.index');
        Route::get('/pelaporan-csv', [PelaporanController::class, 'pelaporanCsv'])->name('pelaporan.csv');
        Route::get('/pelaporan-excel', [PelaporanController::class, 'pelaporanExcel'])->name('pelaporan.excel');
        Route::get('/pelaporan-pdf', [PelaporanController::class, 'pelaporanPdf'])->name('pelaporan.pdf');

        Route::get('/pelaporan-d2d', [PelaporanD2dController::class, 'index'])->name('pelaporan-d2d.index');
        Route::get('/pelaporan-d2d-csv', [PelaporanD2dController::class, 'pelaporanCsv'])->name('pelaporan-d2d.csv');
        Route::get('/pelaporan-d2d-excel', [PelaporanD2dController::class, 'pelaporanExcel'])->name('pelaporan-d2d.excel');
        Route::get('/pelaporan-d2d-pdf', [PelaporanD2dController::class, 'pelaporanPdf'])->name('pelaporan-d2d.pdf');



        // Route::get('/downloads', [BackController::class, 'download']);
        // Route::get('/verifikasi', [BackController::class, 'verifikasi'])->name('verifikasi.index');
        // Route::get('/verifikasi-detail', [BackController::class, 'verifikasi_detail'])->name('verifikasi-detail.index');
        Route::get('/pelaporans', [BackController::class, 'pelaporan'])->name('pelaporan.indexs');

        Route::get('/get-districts', [WilayahController::class, 'getDistricts'])->name('getDistricts');
        Route::get('/get-samsat-by-kabkota', [WilayahController::class, 'getSamsatByKabkota'])->name('getSamsatByKabkota');
        Route::get('/get-samsat-kecamatan', [WilayahController::class, 'getSamsatKecamatan'])->name('getSamsatKecamatan');
        Route::get('/get-samsat-kelurahan', [WilayahController::class, 'getSamsatKelurahan'])->name('getSamsatKelurahan');
        Route::get('/perbandingan-kode-wilayah', [PerbandinganKodeWilayahController::class, 'index'])->name('perbandingan-kode-wilayah.index');
        Route::post('/perbandingan-kode-wilayah/update-wilayah', [PerbandinganKodeWilayahController::class, 'updateKodeSamsatWilayah'])->name('perbandingan-kode-wilayah.update-wilayah');
        Route::post('/perbandingan-kode-wilayah/update-kelurahan', [PerbandinganKodeWilayahController::class, 'updateKodeDagriKelurahan'])->name('perbandingan-kode-wilayah.update-kelurahan');
        Route::get('/perbandingan-kode-wilayah/wilayah-children', [PerbandinganKodeWilayahController::class, 'getWilayahChildren'])->name('perbandingan-kode-wilayah.wilayah-children');
        Route::get('/perbandingan-kode-wilayah/wilayah-detail', [PerbandinganKodeWilayahController::class, 'getWilayahDetail'])->name('perbandingan-kode-wilayah.wilayah-detail');
        Route::get('/perbandingan-kode-wilayah/kecamatan-by-samsat', [PerbandinganKodeWilayahController::class, 'getKecamatanBySamsat'])->name('perbandingan-kode-wilayah.kecamatan-by-samsat');
        Route::get('/perbandingan-kode-wilayah/kelurahan-by-kecamatan', [PerbandinganKodeWilayahController::class, 'getKelurahanByKecamatan'])->name('perbandingan-kode-wilayah.kelurahan-by-kecamatan');
        Route::get('/perbandingan-kode-wilayah/kelurahan-detail', [PerbandinganKodeWilayahController::class, 'getKelurahanDetail'])->name('perbandingan-kode-wilayah.kelurahan-detail');

        Route::get('/data-tertagih', [DataTertagihController::class, 'index'])->name('data-tertagih.index');
        Route::post('/data-tertagih/import/upload', [DataTertagihController::class, 'importUpload'])->name('data-tertagih.import.upload');
        Route::post('/data-tertagih/import/chunk', [DataTertagihController::class, 'importChunk'])->name('data-tertagih.import.chunk');
        Route::get('/data-tertagih/template/{format}/{type}', [DataTertagihController::class, 'downloadTemplate'])
            ->whereIn('format', ['csv', 'xlsx'])
            ->whereIn('type', ['format', 'contoh'])
            ->name('data-tertagih.template');
        Route::post('/data-tertagih/{id}/status', [DataTertagihController::class, 'updateStatus'])->name('data-tertagih.update-status');
        Route::delete('/data-tertagih/{id}', [DataTertagihController::class, 'destroy'])->name('data-tertagih.destroy');

        Route::get('/data-tertagih-d2d', [DataTertagihD2dController::class, 'index'])->name('data-tertagih-d2d.index');
        Route::post('/data-tertagih-d2d/import/upload', [DataTertagihD2dController::class, 'importUpload'])->name('data-tertagih-d2d.import.upload');
        Route::post('/data-tertagih-d2d/import/chunk', [DataTertagihD2dController::class, 'importChunk'])->name('data-tertagih-d2d.import.chunk');
        Route::get('/data-tertagih-d2d/template/{format}/{type}', [DataTertagihD2dController::class, 'downloadTemplate'])
            ->whereIn('format', ['csv', 'xlsx'])
            ->whereIn('type', ['format', 'contoh'])
            ->name('data-tertagih-d2d.template');
        Route::post('/data-tertagih-d2d/{id}/status', [DataTertagihD2dController::class, 'updateStatus'])->name('data-tertagih-d2d.update-status');
        Route::delete('/data-tertagih-d2d/{id}', [DataTertagihD2dController::class, 'destroy'])->name('data-tertagih-d2d.destroy');

        Route::get('/cache-management', [CacheManagementController::class, 'index'])->name('cache-management.index');
        Route::get('/cache-management/{scope}', [CacheManagementController::class, 'scope'])
            ->whereIn('scope', ['admin', 'api'])
            ->name('cache-management.scope');
        Route::post('/cache-management/{scope}/clear-selected', [CacheManagementController::class, 'clearSelected'])
            ->whereIn('scope', ['admin', 'api'])
            ->name('cache-management.clear-selected');
        Route::post('/cache-management/{scope}/clear-group', [CacheManagementController::class, 'clearGroup'])
            ->whereIn('scope', ['admin', 'api'])
            ->name('cache-management.clear-group');
        Route::post('/cache-management/{scope}/clear-all', [CacheManagementController::class, 'clearAll'])
            ->whereIn('scope', ['admin', 'api'])
            ->name('cache-management.clear-all');

        Route::get('/maintenance-status', [MaintenanceStatusController::class, 'index'])->name('maintenance-status.index');
        Route::post('/maintenance-status', [MaintenanceStatusController::class, 'update'])->name('maintenance-status.update');

        Route::get('/version', [VersionController::class, 'index'])->name('version.index');
        Route::post('/version', [VersionController::class, 'store'])->name('version.store');
        Route::put('/version/{id}', [VersionController::class, 'update'])->name('version.update');
        Route::delete('/version/{id}', [VersionController::class, 'destroy'])->name('version.destroy');

        Route::get('/jasa-raharja', [JasaRaharjaController::class, 'index'])->name('jasa-raharja.index');
        Route::post('/jasa-raharja', [JasaRaharjaController::class, 'store'])->name('jasa-raharja.store');
        Route::put('/jasa-raharja/{id}', [JasaRaharjaController::class, 'update'])->name('jasa-raharja.update');
        Route::delete('/jasa-raharja/{id}', [JasaRaharjaController::class, 'destroy'])->name('jasa-raharja.destroy');
    });
});

Route::get('/surat_pernyataan/{id}', [VerifikasiController::class, 'suratPernyataan'])->name('surat.pernyataan');
Route::get('/surat_pernyataan_d2d/{id}', [VerifikasiD2dController::class, 'suratPernyataan'])->name('surat.pernyataan.d2d');

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/act_login', [AuthController::class, 'login'])->name('login.action');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// OTP Routes
Route::get('/otp', [AuthController::class, 'showOtpForm'])->name('login.otp.form');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('login.otp.resend');
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/test', fn() => response()->json([
    'uri' => request()->getRequestUri(),
    'path' => request()->path(),
    'url' => request()->url(),
]));
