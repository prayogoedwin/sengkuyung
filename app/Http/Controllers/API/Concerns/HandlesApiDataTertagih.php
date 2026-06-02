<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\User;
use App\Models\SengSaamsat;
use App\Models\SengPendataanKendaraan;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

trait HandlesApiDataTertagih
{
    /**
     * @return class-string<Model>
     */
    abstract protected function dataTertagihModelClass(): string;

    /**
     * @return array<string, string>
     */
    abstract protected function dataTertagihWilayahValidationMessages(): array;

    /**
     * Tabel pendataan untuk cek nopol sudah didata (show detail).
     *
     * @return class-string<EloquentModel>
     */
    protected function pendataanModelClassForTertagihCheck(): string
    {
        return SengPendataanKendaraan::class;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $modelClass = $this->dataTertagihModelClass();

        $wilayahInput = [
            'lokasi_samsat' => $request->input('lokasi_samsat', $user?->lokasi_samsat),
            'kecamatan_samsat' => $request->input('kecamatan_samsat', $user?->kecamatan_samsat),
            'kelurahan_samsat' => $request->input('kelurahan_samsat', $user?->kelurahan_samsat),
            'year' => $request->input('year'),
            'no_polisi' => $request->input('no_polisi'),
            'alamat' => $request->input('alamat'),
            'page' => $request->input('page'),
            'per_page' => $request->input('per_page'),
        ];

        $validator = Validator::make($wilayahInput, [
            'lokasi_samsat' => 'required|string|max:100',
            'kecamatan_samsat' => 'required|string|max:100',
            'kelurahan_samsat' => 'required|string|max:100',
            'year' => 'nullable|integer|min:2000|max:2100',
            'no_polisi' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], $this->dataTertagihWilayahValidationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $year = (int) ($wilayahInput['year'] ?? date('Y'));
        $perPage = (int) ($wilayahInput['per_page'] ?? 15);

        $lokasiVariants = SengSaamsat::lokasiFilterVariants($wilayahInput['lokasi_samsat']);
        $kecVariants = $this->samsatCodeVariants($wilayahInput['kecamatan_samsat']);
        $kelVariants = $this->samsatCodeVariants($wilayahInput['kelurahan_samsat']);

        $query = $modelClass::query()
            ->whereIn('id_lokasi_samsat', $lokasiVariants)
            ->whereIn('id_kecamatan', $kecVariants)
            ->whereIn('id_kelurahan', $kelVariants)
            ->where('is_terdata', 0)
            ->where('year', $year);

        if (!empty($wilayahInput['no_polisi'])) {
            $query->where('no_polisi', 'like', '%' . $wilayahInput['no_polisi'] . '%');
        }

        if (!empty($wilayahInput['alamat'])) {
            $term = trim((string) $wilayahInput['alamat']);
            if ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('alamat', 'like', '%' . $term . '%')
                        ->orWhere('nm_kelurahan', 'like', '%' . $term . '%')
                        ->orWhere('nm_kecamatan', 'like', '%' . $term . '%')
                        ->orWhere('lokasi_layanan', 'like', '%' . $term . '%');
                });
            }
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginator->items())->map(function (Model $item) {
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

    public function show($id)
    {
        // Route param selalu string; pendataan/data-tertagih pakai integer ID di DB.
        if (!is_numeric($id)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $modelClass = $this->dataTertagihModelClass();
        $item = $modelClass::find((int) $id);

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
            $pendataan = $this->pendataanModelClassForTertagihCheck()::query()
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

    private function itemMatchesUserWilayah(Model $item, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $lokasiVariants = SengSaamsat::lokasiFilterVariants($user->lokasi_samsat);
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
