<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DataTertagih;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataTertagihController extends Controller
{
    /**
     * Daftar data tertagih (belum terdata) dengan filter wilayah samsat + tahun + nopol, ber-pagination.
     *
     * Payload wajib: lokasi_samsat, kecamatan_samsat, kelurahan_samsat (sesuai field user login).
     * Dipetakan ke kolom: id_lokasi_samsat, id_kecamatan, id_kelurahan.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lokasi_samsat' => 'required|string|max:100',
            'kecamatan_samsat' => 'required|string|max:100',
            'kelurahan_samsat' => 'required|string|max:100',
            'year' => 'nullable|integer|min:2000|max:2100',
            'no_polisi' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $year = (int) $request->input('year', (int) date('Y'));
        $perPage = (int) $request->input('per_page', 15);

        $query = DataTertagih::query()
            ->where('id_lokasi_samsat', $request->input('lokasi_samsat'))
            ->where('id_kecamatan', $request->input('kecamatan_samsat'))
            ->where('id_kelurahan', $request->input('kelurahan_samsat'))
            ->where('is_terdata', 0)
            ->where('year', $year);

        if ($request->filled('no_polisi')) {
            $query->where('no_polisi', 'like', '%' . $request->input('no_polisi') . '%');
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
