<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DataTertagih;
use App\Models\SengPendataanKendaraan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $lokasiVariants = $this->samsatCodeVariants($request->input('lokasi_samsat'));
        $kecVariants = $this->samsatCodeVariants($request->input('kecamatan_samsat'));
        $kelVariants = $this->samsatCodeVariants($request->input('kelurahan_samsat'));

        $query = DataTertagih::query()
            ->whereIn('id_lokasi_samsat', $lokasiVariants)
            ->whereIn('id_kecamatan', $kecVariants)
            ->whereIn('id_kelurahan', $kelVariants)
            ->where('is_terdata', 0)
            ->where('year', $year);

        if ($request->filled('no_polisi')) {
            $query->where('no_polisi', 'like', '%' . $request->input('no_polisi') . '%');
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (DataTertagih $item) {
            return [
                'id' => $item->id,
                'no_polisi' => $item->no_polisi,
                'id_lokasi_samsat' => $item->id_lokasi_samsat,
                'lokasi_layanan' => $item->lokasi_layanan,
                'id_kecamatan' => $item->id_kecamatan,
                'nm_kecamatan' => $item->nm_kecamatan,
                'id_kelurahan' => $item->id_kelurahan,
                'nm_kelurahan' => $item->nm_kelurahan,
                'alamat' => $item->alamat,
                'is_terdata' => (int) $item->is_terdata,
                'year' => (int) $item->year,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $items,
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

    public function show(int $id)
    {
        $item = DataTertagih::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $currentUserId = Auth::id();
        $normalizedNopol = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $item->no_polisi) ?? '');

        $pendataan = null;
        if ($normalizedNopol !== '') {
            $pendataan = SengPendataanKendaraan::query()
                ->whereRaw("REPLACE(UPPER(nopol), ' ', '') = ?", [$normalizedNopol])
                ->orderByDesc('id')
                ->first(['id', 'nopol', 'nama', 'created_by', 'created_at']);
        }

        $alreadyClaimedByOtherUser = $pendataan && (int) $pendataan->created_by !== (int) $currentUserId;

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => [
                'id' => $item->id,
                'no_polisi' => $item->no_polisi,
                'id_lokasi_samsat' => $item->id_lokasi_samsat,
                'lokasi_layanan' => $item->lokasi_layanan,
                'id_kecamatan' => $item->id_kecamatan,
                'nm_kecamatan' => $item->nm_kecamatan,
                'id_kelurahan' => $item->id_kelurahan,
                'nm_kelurahan' => $item->nm_kelurahan,
                'alamat' => $item->alamat,
                'is_terdata' => (int) $item->is_terdata,
                'year' => (int) $item->year,
                'can_select' => !$alreadyClaimedByOtherUser,
                'warning_message' => $alreadyClaimedByOtherUser ? 'Nopol ini tidak bisa dipilih, karena sudah didata oleh user lain.' : null,
                'pendataan' => $pendataan ? [
                    'id' => $pendataan->id,
                    'nopol' => $pendataan->nopol,
                    'nama' => $pendataan->nama,
                    'created_by' => $pendataan->created_by,
                    'created_at' => $pendataan->created_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Kode dari profil user (mis. "01", "0105", "0105007") sering beda format dengan isi impor CSV
     * (mis. "1", "105", "105007"). Bangun beberapa varian perbandingan agar WHERE tetap cocok.
     *
     * @return list<string>
     */
    private function samsatCodeVariants(?string $value): array
    {
        $v = trim((string) $value);
        if ($v === '') {
            return [];
        }

        $out = [$v];

        if (ctype_digit($v)) {
            $stripped = ltrim($v, '0');
            $stripped = $stripped === '' ? '0' : $stripped;
            $out[] = $stripped;
            $out[] = (string) (int) $v;
        }

        return array_values(array_unique($out));
    }
}
