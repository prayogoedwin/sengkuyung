<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WilayahSamsat;
use App\Models\SengPendataanKendaraan;
use Illuminate\Database\Eloquent\Model;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use App\Models\SengSaamsat;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\FileEncryption;
use Illuminate\Support\Facades\Storage;
use App\Models\ActivityLog;
use App\Support\ApiCacheManager;
use App\Support\PendataanWilayahFilter;
use App\Support\VerifikasiStatusGroups;
use App\Services\ForceDeletePendataanService;

class VerifikasiController extends Controller
{
    /**
     * @return class-string<SengPendataanKendaraan>
     */
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraan::class;
    }

    protected function verifikasiRouteIndex(): string
    {
        return 'verifikasi.index';
    }

    protected function verifikasiRouteDetail(): string
    {
        return 'verifikasi-detail.index';
    }

    protected function verifikasiRouteStatus(): string
    {
        return 'verifikasi.status';
    }

    protected function verifikasiRouteForceDestroy(): string
    {
        return 'verifikasi.force-destroy';
    }

    protected function isD2dForceDelete(): bool
    {
        return false;
    }

    protected function verifikasiViewIndex(): string
    {
        return 'backend.verifikasis.index';
    }

    protected function verifikasiViewShow(): string
    {
        return 'backend.verifikasis.show';
    }

    protected function verifikasiPageTitle(): string
    {
        return 'Verifikasi';
    }

    protected function findPendataan(int $id): ?Model
    {
        return $this->pendataanModelClass()::find($id);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $user = Auth::user();
            $userId = $user->id ?? null;
            $userKotaId = PendataanWilayahFilter::resolveScopedUserKabkotaId($user);
            $userLokasiSamsat = $user->lokasi_samsat ?? null;
            $userKecamatanSamsat = $user->kecamatan_samsat ?? null;
            $userKelurahanSamsat = $user->kelurahan_samsat ?? null;
            $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));
            $isAdminProv = $user && ($user->hasRole('admin') || $user->hasRole('adminprov'));
            $isUppd = $user && $user->hasRole('uppd');
            $isUptd = $user && ($user->hasRole('uptd') || $isUppd);
            $isKabkota = $user && $user->hasRole('kabkota');
            $resolvedKabkotaId = $this->resolveKabkotaIdFromLokasiSamsat($userLokasiSamsat);
            $effectiveKotaId = $userKotaId ?: $resolvedKabkotaId;
            $nopolSearch = $this->resolveNopolSearch($request);

            $verifikasis = $this->pendataanModelClass()::query();

            if ($nopolSearch !== '' && ($isSuperAdmin || $isAdminProv)) {
                $this->applyNopolSearchFilter($verifikasis, $nopolSearch);
            } elseif ($nopolSearch !== '' && $isUppd) {
                $this->applyUppdNopolSearchScope($verifikasis, $user, $effectiveKotaId);
                $this->applyNopolSearchFilter($verifikasis, $nopolSearch);
            } else {
                $this->applyVerifikasiIndexFilters(
                    $verifikasis,
                    $request,
                    $user,
                    $userId,
                    $isSuperAdmin,
                    $isAdminProv,
                    $isUptd,
                    $isKabkota,
                    $effectiveKotaId,
                    $userKecamatanSamsat,
                    $userKelurahanSamsat,
                    $nopolSearch
                );
            }

                // return DataTables::of($verifikasis)
                // ->addIndexColumn()
                // ->addColumn('nopol', function ($verifikasi) {
                //     return $verifikasi->nopol ? $verifikasi->nopol : 'N/A';
                // })
                // ->addColumn('tanggal_pendataan', function ($verifikasi) {
                //     return $verifikasi->created_at ?  Carbon::parse($verifikasi->created_at)->format('Y-m-d H:i:s') : 'N/A';
                // })
                // ->addColumn('nama', function ($verifikasi) {
                //     return $verifikasi->nama ? $verifikasi->nama : 'N/A';
                // })
                // ->addColumn('nohp', function ($verifikasi) {
                //     return $verifikasi->nohp ? $verifikasi->nohp : 'N/A';
                // })
                // ->addColumn('status_name', function ($verifikasi) {
                //     return $verifikasi->status_name ? $verifikasi->status_name : 'N/A';
                // })
                // ->addColumn('status_verifikasi_name', function ($verifikasi) {
                //     return $verifikasi->status_verifikasi_name ? $verifikasi->status_verifikasi_name : 'N/A';
                // })

                
                // ->addColumn('options', function ($verifikasi) {
                //     return '
                //         <a href="' . route('verifikasi-detail.index', ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-primary btn-sm">Verif</a>
                //         <button hidden class="btn btn-danger btn-sm" onclick="confirmDelete(' . Helper::encodeId($verifikasi->id) . ')">Delete</button>
                //     ';
                // })
                // ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                // ->make(true);

                $verifikasis->orderBy('id', 'desc');

                $datatable = DataTables::of($verifikasis)
                    ->addIndexColumn()
                    ->addColumn('nopol', function ($verifikasi) {
                        return $verifikasi->nopol ?? 'N/A';
                    })
                    ->addColumn('tanggal_pendataan', function ($verifikasi) {
                        return $verifikasi->created_at ? Carbon::parse($verifikasi->created_at)->format('Y-m-d H:i:s') : 'N/A';
                    })
                    ->addColumn('nama', function ($verifikasi) {
                        return $verifikasi->nama ?? 'N/A';
                    })
                    ->addColumn('nohp', function ($verifikasi) {
                        return $verifikasi->nohp ?? 'N/A';
                    })
                    ->addColumn('status_name', function ($verifikasi) {
                        return $verifikasi->status_name ?? 'N/A';
                    })
                    ->addColumn('status_verifikasi_name', function ($verifikasi) {
                        return $verifikasi->status_verifikasi_name ?? 'N/A';
                    });

                // Tambahkan kolom options berdasarkan role
                if ($isSuperAdmin || $isAdminProv || $isUptd) {
                    $datatable->addColumn('options', function ($verifikasi) use ($isSuperAdmin) {
                        $buttons = '<a href="' . route($this->verifikasiRouteDetail(), ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-primary btn-sm">Verif</a>';

                        if ($isSuperAdmin) {
                            $buttons .= ' <button type="button" class="btn btn-danger btn-sm" onclick="forceDeleteVerifikasi(' . (int) $verifikasi->id . ')">Force Delete</button>';
                        }

                        return $buttons;
                    });
                } elseif ($isKabkota) {
                    $datatable->addColumn('options', function ($verifikasi) {
                        return '
                            <a href="' . route($this->verifikasiRouteDetail(), ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-info btn-sm">Lihat</a>
                        ';
                    });
                } else {
                    $datatable->addColumn('options', function ($verifikasi) {
                        return '-';
                    });
                }

                return $datatable
                    ->rawColumns(['options'])
                    ->make(true);

        }

        $status_verifikasis = ApiCacheManager::remember('admin:master:status-verifikasi:all', ApiCacheManager::masterTtl(), static function () {
            return SengStatusVerifikasi::select('*')->get();
        });

        $kabkotas = ApiCacheManager::remember('admin:master:kabkota:all', ApiCacheManager::masterTtl(), static function () {
            return SengWilayah::select('*')
                ->where('id_up', 33)
                ->get();
        });

        $user = Auth::user();
        $isUppd = $user && $user->hasRole('uppd');
        $isKabkotaRole = $user && $user->hasRole('kabkota');
        $userLokasiSamsat = SengSaamsat::resolveDropdownLokasiId($user->lokasi_samsat ?? null) ?? '';
        $lockLokasiSamsat = $userLokasiSamsat !== ''
            && ! $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov', 'uppd', 'uptd']);
        $resolvedKabkotaId = $this->resolveKabkotaIdFromLokasiSamsat($user->lokasi_samsat ?? null);
        $selectedKabkotaId = $user->kota ?? $resolvedKabkotaId;

        if (($isUppd || $isKabkotaRole) && !empty($selectedKabkotaId)) {
            $kabkotas = $kabkotas->filter(function ($kbkt) use ($selectedKabkotaId) {
                return (string) $kbkt->id === (string) $selectedKabkotaId;
            })->values();
        }

        return view($this->verifikasiViewIndex(), compact(
            'kabkotas',
            'status_verifikasis',
            'selectedKabkotaId',
            'isUppd',
            'isKabkotaRole',
            'userLokasiSamsat',
            'lockLokasiSamsat'
        ))->with([
            'verifikasiIndexRoute' => $this->verifikasiRouteIndex(),
            'verifikasiPageTitle' => $this->verifikasiPageTitle(),
            'verifikasiForceDestroyRoute' => $this->verifikasiRouteForceDestroy(),
            'isSuperAdmin' => $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin')),
        ]);
    }

    public function forceDestroy(int $id, ForceDeletePendataanService $service)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));
        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');

        $result = $service->forceDeleteFromPendataan($id, $this->isD2dForceDelete());

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Force delete berhasil. Pendataan: %d, Tertagih: %d (sudah diarsipkan ke tabel _del).',
                $result['pendataan'],
                $result['tertagih']
            ),
            'result' => $result,
        ]);
    }

    private function resolveKabkotaIdFromLokasiSamsat(?string $lokasiSamsatId): ?string
    {
        if (empty($lokasiSamsatId)) {
            return null;
        }

        $cacheKey = 'admin:master:wilayah-samsat:kabkota-by-lokasi:' . (string) $lokasiSamsatId;

        return ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($lokasiSamsatId) {
            $row = WilayahSamsat::select('kabkota')
                ->where('id', $lokasiSamsatId)
                ->first();

            if ($row?->kabkota) {
                return (string) $row->kabkota;
            }

            $samsat = SengSaamsat::select('kabkota')
                ->where('id_wilayah_samsat', $lokasiSamsatId)
                ->orWhere('id', $lokasiSamsatId)
                ->first();

            return $samsat?->kabkota ? (string) $samsat->kabkota : null;
        });
    }

    private function resolveNopolSearch(Request $request): string
    {
        return trim((string) ($request->nopol ?? ''));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyNopolSearchFilter($query, string $nopolSearch): void
    {
        $query->where('nopol', 'like', '%' . $nopolSearch . '%');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyUppdNopolSearchScope($query, ?User $user, ?string $effectiveKotaId): void
    {
        $profileLokasi = trim((string) ($user?->lokasi_samsat ?? ''));

        if ($profileLokasi !== '') {
            PendataanWilayahFilter::applyLokasiSamsatFilter($query, $profileLokasi);

            return;
        }

        if (!empty($effectiveKotaId)) {
            $lokasiVariants = SengSaamsat::lokasiFilterVariantsByKabkota((string) $effectiveKotaId);
            if ($lokasiVariants !== []) {
                $query->whereIn('kota', $lokasiVariants);
            } else {
                $query->where('kota_dagri', $effectiveKotaId);
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyVerifikasiIndexFilters(
        $query,
        Request $request,
        ?User $user,
        ?int $userId,
        bool $isSuperAdmin,
        bool $isAdminProv,
        bool $isUptd,
        bool $isKabkota,
        ?string $effectiveKotaId,
        ?string $userKecamatanSamsat,
        ?string $userKelurahanSamsat,
        string $nopolSearch
    ): void {
        if ($isSuperAdmin || $isAdminProv) {
            if ($request->kota) {
                $query->where('kota_dagri', $request->kota);
            }
        } elseif ($isUptd || $isKabkota) {
            if (!empty($effectiveKotaId)) {
                $query->where('kota_dagri', $effectiveKotaId);
            } elseif ($request->kota) {
                $query->where('kota_dagri', $request->kota);
            }
        } elseif ($user && $user->hasAnyRole(['petugas', 'petugas-d2d'])) {
            $query->where('created_by', $userId);
        }

        if ($request->status_verifikasi_id) {
            $query->where('status_verifikasi', $request->status_verifikasi_id);
        } else {
            $query->whereIn('status_verifikasi', VerifikasiStatusGroups::menungguIds());
        }

        $resolvedLokasi = PendataanWilayahFilter::resolveLokasiSamsatFilterValue($user, $request->lokasi_samsat);
        if ($resolvedLokasi !== '') {
            PendataanWilayahFilter::applyLokasiSamsatFilter($query, $resolvedLokasi);
        }

        if (!empty($userKecamatanSamsat)) {
            PendataanWilayahFilter::applyKecamatanFilter($query, (string) $userKecamatanSamsat);
        } elseif ($request->kecamatan_samsat) {
            PendataanWilayahFilter::applyKecamatanFilter($query, (string) $request->kecamatan_samsat);
        }

        if (!empty($userKelurahanSamsat)) {
            $query->where('desa', $userKelurahanSamsat);
        } elseif ($request->kelurahan_samsat) {
            $query->where('desa', $request->kelurahan_samsat);
        }

        if ($nopolSearch !== '') {
            $this->applyNopolSearchFilter($query, $nopolSearch);
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
    }

    // Show a single record
    public function show_bak($id)
    {
        // Decode ID from request

        $decodedId = Helper::decodeId($id);

        // Find data based on ID
        $data = SengPendataanKendaraan::find($decodedId);
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        // If data is not found
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        $html = null;
        if($data->status == 2){
            $data_html = [
                'nama' => $data->nama, // Ganti dengan variabel atau data dari DB
                'alamat' => $data->alamat.''.$data->desa_name.''.$data->kec_name,
                'kota' => $data->kota_name,
                'no_polisi' => $data->nopol,
                'merk' => $data->merk,
                'tipe' => $data->tipe,
                'tanggal' => now()->format('d F Y') // Format tanggal: 20 Februari 2025
            ];
            $html = view('backend/html/surat_pernyataan', $data_html)->render();
        }

        // Return the view with the data
        return view('backend.verifikasis.show',  compact('data', 'status_verifikasis', 'html'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $verifikasiReadOnly = $user && $user->hasRole('kabkota');

        $decodedId = Helper::decodeId($id);
        $data = $this->findPendataan($decodedId);
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($verifikasiReadOnly && !$this->kabkotaCanAccessPendataan($user, $data)) {
            abort(403, 'Anda tidak berhak melihat data di luar wilayah kabupaten/kota Anda.');
        }

        $activityLogs = collect();
        if (!$verifikasiReadOnly) {
            $activityLogs = ActivityLog::where('id_kode', $id)
                ->whereIn('method', ['POST', 'PUT'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $kabkotaDisplay = $data->kota_name;
        if (!empty($data->kota_dagri)) {
            $kabkotaDisplay = ApiCacheManager::remember(
                'admin:master:kabkota:name-by-id:' . (string) $data->kota_dagri,
                ApiCacheManager::masterTtl(),
                static function () use ($data) {
                    $wilayah = SengWilayah::select('nama')->where('id', (string) $data->kota_dagri)->first();
                    return $wilayah?->nama ?: null;
                }
            ) ?: $kabkotaDisplay;
        }

        $lokasiSamsatDisplay = $data->kota_name;
        if (!empty($data->kota)) {
            $lokasiSamsatDisplay = ApiCacheManager::remember(
                'admin:master:seng-samsat:nama-by-id:' . (string) $data->kota,
                ApiCacheManager::masterTtl(),
                static function () use ($data) {
                    $samsat = SengSaamsat::select('lokasi', 'lokasi_singkat')
                        ->where('id_wilayah_samsat', (string) $data->kota)
                        ->orWhere('id', (string) $data->kota)
                        ->first();

                    if (!$samsat) {
                        return null;
                    }

                    $lokasi = trim((string) ($samsat->lokasi ?? ''));
                    $lokasiSingkat = trim((string) ($samsat->lokasi_singkat ?? ''));

                    if ($lokasi !== '' && $lokasiSingkat !== '') {
                        return $lokasi . ' (' . $lokasiSingkat . ')';
                    }

                    return $lokasi !== '' ? $lokasi : ($lokasiSingkat !== '' ? $lokasiSingkat : null);
                }
            ) ?: $lokasiSamsatDisplay;
        }

        // $name_tipe = Helper::getTipe($request->tipe);
        $name_tipe = 'Ganti Kepemilikan / TDA';
        $html = null;
        if($data->status == 2 || $data->status == 10){
            $data_html = [
                'nama' => $data->nama,
                'nama_pembuat_pernyataan' => $data->nama_pembuat_pernyataan,
                'alamat' => $data->alamat.''.$data->desa_name.''.$data->kec_name,
                'kota' => $data->kota_name,
                'no_polisi' => $data->nopol,
                'merk' => $data->merk,
                'tipe' => $data->tipe,
                'nama_tipe' => $name_tipe,
                'tanggal' => now()->format('d F Y')
            ];
            $html = view('backend/html/surat_pernyataan', $data_html)->render();
        }

        // Decrypt files jika encrypted
        $decryptedFiles = [];
        for ($i = 0; $i <= 9; $i++) {
            $fileKey = "file{$i}";
            $fileUrl = $data->{$fileKey . "_url"};
            $fileKet = $data->{$fileKey . "_ket"};
            $isEncrypted = $data->{$fileKey . "_encrypted"} ?? 0;
            
            // Cek encrypted apa adanya — file di disk pasti perlu di-decrypt kalau flag encrypted=1.
            // Label `_ket` boleh "KTP" (lama) atau "Foto Identitas Pemilik" (baru).
            if ($fileUrl && $isEncrypted) {
                // File adalah identitas pemilik yang ter-encrypt
                $filePath = str_replace('storage/', '', $fileUrl);
                
                if (Storage::disk('public')->exists($filePath)) {
                    // Baca file encrypted
                    $encryptedContent = Storage::disk('public')->get($filePath);
                    
                    // Decrypt file
                    $decryptedContent = FileEncryption::decryptFile($encryptedContent);
                    
                    // Convert ke base64
                    $originalExt = $data->{$fileKey . "_original_ext"} ?? 'jpg';
                    $mimeType = $this->getMimeType($originalExt);
                    $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($decryptedContent);
                    
                    $decryptedFiles[$fileKey] = $base64;
                }

            }
            // else{

            //     $filePath = str_replace('storage/', '', $fileUrl);
                
            //     if (Storage::disk('public')->exists($filePath)) {
            //         // Baca file encrypted
            //         $encryptedContent = Storage::disk('public')->get($filePath);
                    
            //         // Convert ke base64
            //         $originalExt = $data->{$fileKey . "_original_ext"} ?? 'jpg';
            //         $mimeType = $this->getMimeType($originalExt);
            //         $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($encryptedContent);
                    
            //         $decryptedFiles[$fileKey] = $base64;
            //     }

            // }
        }

        return view($this->verifikasiViewShow(), compact(
            'data',
            'status_verifikasis',
            'html',
            'decryptedFiles',
            'activityLogs',
            'kabkotaDisplay',
            'lokasiSamsatDisplay',
            'verifikasiReadOnly'
        ))->with([
            'verifikasiStatusRoute' => $this->verifikasiRouteStatus(),
            'verifikasiPageTitle' => $this->verifikasiPageTitle(),
        ]);
    }

    private function kabkotaCanAccessPendataan(User $user, Model $data): bool
    {
        $userKotaId = $user->kota ?? null;
        $resolvedKabkotaId = $this->resolveKabkotaIdFromLokasiSamsat($user->lokasi_samsat ?? null);
        $effectiveKotaId = $userKotaId ?: $resolvedKabkotaId;

        if (empty($effectiveKotaId)) {
            return true;
        }

        return (string) $data->kota_dagri === (string) $effectiveKotaId;
    }

    // Helper method untuk get mime type
    private function getMimeType($extension)
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    public function verif(Request $request, $id)
    {
        if (Auth::user()?->hasRole('kabkota')) {
            abort(403, 'Akun kabkota hanya dapat melihat data verifikasi.');
        }

        // Decode ID from request
        $decodedId = Helper::decodeId($request->id);

        // Find the record by the decoded ID
        $data = $this->findPendataan($decodedId);
        $status_verifikasi = SengStatusVerifikasi::find($request->status_verifikasi_id);

        // Check if the record exists
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Update the status_verifikasi field
        $data->status_verifikasi = $request->status_verifikasi_id;
        $data->status_verifikasi_name = $status_verifikasi->nama;
        $data->file9_ket = $request->keterangan;

        // Save the changes
        if ($data->save()) {
            // Redirect to the detail page upon successful update
            return redirect()->route($this->verifikasiRouteDetail(), ['id' => Helper::encodeId($data->id)])->with('success', 'Status updated successfully.');
        } else {
            return back()->with('error', 'Failed to update status.');
        }
    }

    public function suratPernyataan($id)
    {
        // Decode ID from request
        $decodedId = Helper::decodeId($id);

        // Find data based on ID
        $data = $this->findPendataan($decodedId);

        // If data is not found
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // $name_tipe = Helper::getTipe($data->tipe);
        $name_tipe = 'GANTI KEPEMILIKAN / TIDAK DIKETAHUI ALAMAT ATAU KEDUDUKANNYA';

        // Prepare data for the view
        $suratData = [
            'nama' => $data->nama,
            'nama_pembuat_pernyataan' => $data->nama_pembuat_pernyataan,
            'alamat' => $data->alamat,
            'desa' => $data->desa_name ?? '',
            'kecamatan' => $data->kec_name ?? '',
            'kota' => $data->kota_name ?? '',
            'no_polisi' => $data->nopol,
            'merk' => $data->merk,
            'tipe' => $data->tipe,
            'nama_tipe' => $name_tipe,
            'tanggal' => now()->locale('id')->isoFormat('D MMMM YYYY') // Format: 20 Februari 2025
        ];

        // Return the view with the data
        return view('backend.template_surat.surat_pernyataan', $suratData);
    }

}
