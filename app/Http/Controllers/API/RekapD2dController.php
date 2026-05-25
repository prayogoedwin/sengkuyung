<?php

namespace App\Http\Controllers\API;

use App\Models\SengPendataanKendaraanD2d;

class RekapD2dController extends RekapController
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    // View di-share dengan versi regular karena struktur data identik (SengPendataanKendaraanD2d
    // mewarisi semua field dari SengPendataanKendaraan). Override method di bawah ini jika
    // suatu saat butuh view khusus D2D.
    // protected function rekapPreviewView(): string { return 'backend.rekap.rekap_mobile_d2d'; }
    // protected function jurnalPreviewView(): string { return 'backend.rekap.jurnal_mobile_d2d'; }
}
