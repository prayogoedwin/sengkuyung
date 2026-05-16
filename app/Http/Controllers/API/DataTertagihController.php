<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DataTertagih;
use App\Models\SengPendataanKendaraan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DataTertagihController extends Controller
{
    /**
     * Daftar data tertagih (belum terdata) dengan filter wilayah samsat + tahun + nopol, ber-pagination.
     *
     * Wilayah diambil dari profil user login; field lokasi/kec/kel samsat di body opsional (override).
     * Hanya role petugas — dicek middleware petugas.api.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $wilayahInput = [
            'lokasi_samsat' => $request->input('lokasi_samsat', $user?->lokasi_samsat),
            'kecamatan_samsat' => $request->input('kecamatan_samsat', $user?->kecamatan_samsat),
            'kelurahan_samsat' => $request->input('kelurahan_samsat', $user?->kelurahan_samsat),
            'year' => $request->input('year'),
            'no_polisi' => $request->input('no_polisi'),
            'page' => $request->input('page'),
            'per_page' => $request->input('per_page'),
        ];

        $validator = Validator::make($wilayahInput, [
            'lokasi_samsat' => 'required|string|max:100',
            'kecamatan_samsat' => 'required|string|max:100',
            'kelurahan_samsat' => 'required|string|max:100',
            'year' => 'nullable|integer|min:2000|max:2100',
            'no_polisi' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], [
            'lokasi_samsat.required' => 'Wilayah samsat tidak lengkap pada profil akun petugas.',
            'kecamatan_samsat.required' => 'Kecamatan samsat tidak lengkap pada profil akun petugas.',
            'kelurahan_samsat.required' => 'Kelurahan samsat tidak lengkap pada profil akun petugas.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $year = (int) ($wilayahInput['year'] ?? date('Y'));
        $perPage = (int) ($wilayahInput['per_page'] ?? 15);

        $lokasiVariants = $this->samsatCodeVariants($wilayahInput['lokasi_samsat']);
        $kecVariants = $this->samsatCodeVariants($wilayahInput['kecamatan_samsat']);
        $kelVariants = $this->samsatCodeVariants($wilayahInput['kelurahan_samsat']);

        $query = DataTertagih::query()
            ->whereIn('id_lokasi_samsat', $lokasiVariants)
            ->whereIn('id_kecamatan', $kecVariants)
            ->whereIn('id_kelurahan', $kelVariants)
            ->where('is_terdata', 0)
            ->where('year', $year);

        if (!empty($wilayahInput['no_polisi'])) {
            $query->where('no_polisi', 'like', '%' . $wilayahInput['no_polisi'] . '%');
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
                'nama_pemilik' => $item->nama_pemilik,
                'jenis_roda' => $item->jenis_roda,
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

        $user = Auth::user();
        if (!$this->itemMatchesUserWilayah($item, $user)) {
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
                'nama_pemilik' => $item->nama_pemilik,
                'jenis_roda' => $item->jenis_roda,
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

    private function itemMatchesUserWilayah(DataTertagih $item, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $lokasiVariants = $this->samsatCodeVariants($user->lokasi_samsat);
        $kecVariants = $this->samsatCodeVariants($user->kecamatan_samsat);
        $kelVariants = $this->samsatCodeVariants($user->kelurahan_samsat);

        if ($lokasiVariants === [] || $kecVariants === [] || $kelVariants === []) {
            return false;
        }

        return in_array((string) $item->id_lokasi_samsat, $lokasiVariants, true)
            && in_array((string) $item->id_kecamatan, $kecVariants, true)
            && in_array((string) $item->id_kelurahan, $kelVariants, true);
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
