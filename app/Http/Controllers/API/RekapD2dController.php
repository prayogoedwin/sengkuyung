<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SengPendataanKendaraanD2d;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;
use App\Support\VerifikasiStatusGroups;

/**
 * Rekap khusus data D2D (seng_pendataan_kendaraan_d2d).
 *
 * Disengaja TIDAK meng-extends RekapController biarpun logikanya mirror —
 * supaya alur reguler vs D2D bisa berkembang terpisah tanpa saling mengganggu.
 * Logika "bucket" status verifikasi tetap dibagi via App\Support\VerifikasiStatusGroups
 * supaya dashboard, halaman verifikasi, dan rekap konsisten satu sumber kebenaran.
 */
class RekapD2dController extends Controller
{
    protected function rekapPreviewView(): string
    {
        return 'backend.rekap.rekap_mobile';
    }

    protected function jurnalPreviewView(): string
    {
        return 'backend.rekap.jurnal_mobile';
    }

    /**
     * Terapkan filter rentang tanggal pada `created_at` dengan benar.
     *
     * Catatan: `whereBetween('created_at', ['2026-05-25', '2026-05-25'])` di MySQL
     * setara dengan `BETWEEN '2026-05-25 00:00:00' AND '2026-05-25 00:00:00'`,
     * sehingga hanya cocok untuk record yang dibuat tepat pukul 00:00:00.
     * Untuk mencakup seluruh hari, kita normalisasi ke `startOfDay`–`endOfDay`.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyTanggalFilter($query, Request $request): void
    {
        $start = $request->tanggal_start;
        $end = $request->tanggal_end;

        if (!$start && !$end) {
            return;
        }

        try {
            $startAt = Carbon::parse($start ?: $end)->startOfDay();
            $endAt = Carbon::parse($end ?: $start)->endOfDay();
        } catch (\Throwable $e) {
            return;
        }

        $query->whereBetween('created_at', [$startAt, $endAt]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $verifikasis = SengPendataanKendaraanD2d::query();

        $verifikasis->where('created_by', $user->id);

        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
        }

        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }

        $this->applyTanggalFilter($verifikasis, $request);

        // Bucket disamakan dengan dashboard: menunggu = MENUNGGU + SUDAH DIPERBAIKI; ditolak = DITOLAK + REVISI.
        $groups = VerifikasiStatusGroups::all();
        $total = (clone $verifikasis)->count();
        $menunggu_verifikasi = (clone $verifikasis)->whereIn('status_verifikasi', $groups['menunggu'])->count();
        $verifikasi = (clone $verifikasis)->whereIn('status_verifikasi', $groups['verifikasi'])->count();
        $ditolak = (clone $verifikasis)->whereIn('status_verifikasi', $groups['ditolak'])->count();

        $pkb = (clone $verifikasis)->sum('pkb_pokok');

        $data = [
            'total' => $total,
            'menunggu_verifikasi' => $menunggu_verifikasi,
            'verifikasi' => $verifikasi,
            'ditolak' => $ditolak,
            'pkb' => $pkb,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $data
        ]);
    }

    public function jurnalPreview(Request $request)
    {
        $user = Helper::decodeId($request->petugas);

        $verifikasis = SengPendataanKendaraanD2d::query();

        $verifikasis->where('created_by', $user);

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
        }

        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }

        $this->applyTanggalFilter($verifikasis, $request);

        $data = $verifikasis->get();

        return view($this->jurnalPreviewView(), compact('data', 'request'));
    }

    public function rekapPreview(Request $request)
    {
        $user = Helper::decodeId($request->petugas);

        $verifikasis = SengPendataanKendaraanD2d::query();

        $verifikasis->where('created_by', $user);

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
        }

        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }

        $this->applyTanggalFilter($verifikasis, $request);

        // Bucket disamakan dengan dashboard: menunggu = MENUNGGU + SUDAH DIPERBAIKI; ditolak = DITOLAK + REVISI.
        $groups = VerifikasiStatusGroups::all();
        $total = (clone $verifikasis)->count();
        $menunggu_verifikasi = (clone $verifikasis)->whereIn('status_verifikasi', $groups['menunggu'])->count();
        $verifikasi = (clone $verifikasis)->whereIn('status_verifikasi', $groups['verifikasi'])->count();
        $ditolak = (clone $verifikasis)->whereIn('status_verifikasi', $groups['ditolak'])->count();

        return view($this->rekapPreviewView(), compact('total', 'menunggu_verifikasi', 'verifikasi', 'ditolak', 'request'));
    }
}
