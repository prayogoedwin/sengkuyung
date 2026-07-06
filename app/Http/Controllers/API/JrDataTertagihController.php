<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DataTertagih;
use App\Models\DataTertagihD2d;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class JrDataTertagihController extends Controller
{
    public function index(Request $request)
    {
        return $this->paginateTertagih($request, DataTertagih::class);
    }

    public function indexD2d(Request $request)
    {
        return $this->paginateTertagih($request, DataTertagihD2d::class);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function paginateTertagih(Request $request, string $modelClass)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'is_terdata' => 'nullable|in:0,1',
            'no_polisi' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $year = (int) ($validated['year'] ?? date('Y'));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = $modelClass::query()->where('year', $year);

        if ($request->filled('is_terdata')) {
            $query->where('is_terdata', (int) $validated['is_terdata']);
        }

        if ($request->filled('no_polisi')) {
            $query->where('no_polisi', 'like', '%' . trim((string) $validated['no_polisi']) . '%');
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);

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
                'status_terdata' => (int) $item->is_terdata === 1 ? 'Terdata' : 'Belum Terdata',
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
}
