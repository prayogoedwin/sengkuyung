<?php

use App\Support\PembayaranTarik;
use App\Support\RekapVisualFilterCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rvf:warm-cache --refresh')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(180)
    ->when(static fn () => RekapVisualFilterCache::shouldDispatchWarm());

Schedule::command('pembayaran:tarik-jadwal')
    ->everyMinute()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(360)
    ->when(static fn () => PembayaranTarik::shouldDispatch());
