<?php

namespace App\Http\Controllers;

use App\Models\SengSaamsat;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;
use Illuminate\Http\Request;

class PerbandinganKodeWilayahController extends Controller
{
    public function index()
    {
        $kotas = SengWilayah::where('id_up', 33)
            ->orderBy('nama')
            ->get();

        $samsats = SengSaamsat::orderBy('lokasi')->get();

        return view('backend.perbandingan-kode-wilayah.index', compact('kotas', 'samsats'));
    }

    public function getWilayahChildren(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|string',
        ]);

        $items = SengWilayah::where('id_up', $request->parent_id)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode', 'kode_samsat']);

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function getWilayahDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $item = SengWilayah::where('id', $request->id)
            ->first(['id', 'nama', 'kode', 'kode_samsat']);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Data wilayah tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateKodeSamsatWilayah(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:kota,kecamatan,kelurahan',
            'wilayah_id' => 'required|string',
            'kode_wilayah' => 'required|string',
            'kode_samsat' => 'required|string|max:100',
        ]);

        $updated = SengWilayah::where('id', $validated['wilayah_id'])
            ->where('kode', $validated['kode_wilayah'])
            ->update([
                'kode_samsat' => $validated['kode_samsat'],
            ]);

        if (!$updated) {
            return redirect()
                ->route('perbandingan-kode-wilayah.index')
                ->with('error', 'Gagal update kode samsat, data wilayah tidak ditemukan.');
        }

        return redirect()
            ->route('perbandingan-kode-wilayah.index')
            ->with('success', 'Kode samsat ' . $validated['level'] . ' berhasil diperbarui.');
    }

    public function getKecamatanBySamsat(Request $request)
    {
        $request->validate([
            'id_lokasi_samsat' => 'required',
        ]);

        $items = SengWilayahKec::where('id_lokasi_samsat', $request->id_lokasi_samsat)
            ->orderBy('kecamatan')
            ->get(['id_kecamatan', 'kecamatan']);

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function getKelurahanByKecamatan(Request $request)
    {
        $request->validate([
            'id_kecamatan' => 'required|string',
        ]);

        $items = SengWilayahKel::where('id_kecamatan', $request->id_kecamatan)
            ->orderBy('kelurahan')
            ->get(['id_kelurahan', 'kelurahan']);

        return response()->json(['success' => true, 'items' => $items]);
    }

    public function getKelurahanDetail(Request $request)
    {
        $request->validate([
            'id_kelurahan' => 'required|string',
        ]);

        $item = SengWilayahKel::where('id_kelurahan', $request->id_kelurahan)
            ->first([
                'id_kelurahan',
                'kelurahan',
                'kode_dagri_kelelurahan',
                'kode_dagri_kecamatan',
                'kode_dagri_kabkota',
            ]);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Data kelurahan tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateKodeDagriKelurahan(Request $request)
    {
        $validated = $request->validate([
            'id_kelurahan' => 'required|string',
            'kode_dagri_kelelurahan' => 'nullable|string|max:100',
            'kode_dagri_kecamatan' => 'nullable|string|max:100',
            'kode_dagri_kabkota' => 'nullable|integer',
        ]);

        $updated = SengWilayahKel::where('id_kelurahan', $validated['id_kelurahan'])
            ->update([
                'kode_dagri_kelelurahan' => $validated['kode_dagri_kelelurahan'] ?? null,
                'kode_dagri_kecamatan' => $validated['kode_dagri_kecamatan'] ?? null,
                'kode_dagri_kabkota' => $validated['kode_dagri_kabkota'] ?? null,
            ]);

        if (!$updated) {
            return redirect()
                ->route('perbandingan-kode-wilayah.index')
                ->with('error', 'Gagal update kode dagri, data kelurahan tidak ditemukan.');
        }

        return redirect()
            ->route('perbandingan-kode-wilayah.index')
            ->with('success', 'Kode dagri kelurahan berhasil diperbarui.');
    }
}
