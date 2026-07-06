<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\SengPendataanKendaraan;
use App\Support\PendataanWilayahFilter;
use App\Support\VerifikasiStatusGroups;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesJrVerifikasi
{
    /**
     * @return class-string<Model>
     */
    abstract protected function jrVerifikasiModelClass(): string;

    protected function paginateJrVerifikasi(Request $request)
    {
        $validated = $request->validate([
            'status_verifikasi_id' => 'nullable|integer',
            'kota' => 'nullable|string|max:20',
            'lokasi_samsat' => 'nullable|string|max:100',
            'kecamatan_samsat' => 'nullable|string|max:100',
            'kelurahan_samsat' => 'nullable|string|max:100',
            'nopol' => 'nullable|string|max:50',
            'tanggal_start' => 'nullable|date',
            'tanggal_end' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $modelClass = $this->jrVerifikasiModelClass();

        $query = $modelClass::query();
        $this->applyJrVerifikasiFilters($query, $request);

        $paginator = $query->orderByDesc('id')->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Model $item) => $this->mapJrVerifikasiItem($item))->values();

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

    /**
     * @param  Builder<SengPendataanKendaraan>  $query
     */
    protected function applyJrVerifikasiFilters(Builder $query, Request $request): void
    {
        $nopolSearch = trim((string) $request->input('nopol', ''));

        if ($nopolSearch !== '') {
            $query->where('nopol', 'like', '%' . $nopolSearch . '%');

            return;
        }

        if ($request->filled('kota')) {
            $query->where('kota_dagri', (string) $request->kota);
        }

        if ($request->filled('status_verifikasi_id')) {
            $query->where('status_verifikasi', (int) $request->status_verifikasi_id);
        } else {
            $query->whereIn('status_verifikasi', VerifikasiStatusGroups::menungguIds());
        }

        if ($request->filled('lokasi_samsat')) {
            PendataanWilayahFilter::applyLokasiSamsatFilter($query, (string) $request->lokasi_samsat);
        }

        if ($request->filled('kecamatan_samsat')) {
            PendataanWilayahFilter::applyKecamatanFilter($query, (string) $request->kecamatan_samsat);
        }

        if ($request->filled('kelurahan_samsat')) {
            $query->where('desa', (string) $request->kelurahan_samsat);
        }

        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapJrVerifikasiItem(Model $item): array
    {
        return [
            'id' => $item->id,
            'nopol' => $item->nopol,
            'tanggal_pendataan' => $item->created_at
                ? Carbon::parse($item->created_at)->format('Y-m-d H:i:s')
                : null,
            'nama' => $item->nama,
            'nohp' => $item->nohp,
            'status' => $item->status,
            'status_name' => $item->status_name,
            'status_verifikasi' => $item->status_verifikasi,
            'status_verifikasi_name' => $item->status_verifikasi_name,
            'kota_dagri' => $item->kota_dagri,
            'kota_name' => $item->kota_name,
            'kec_name' => $item->kec_name,
            'desa_name' => $item->desa_name,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
