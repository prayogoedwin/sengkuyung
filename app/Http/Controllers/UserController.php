<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use App\Models\SengWilayah;
use App\Models\SengSaamsat;
use App\Models\SengWilayahKel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Support\ApiCacheManager;

// class UserController extends Controller implements HasMiddleware
class UserController extends Controller
{
    private const PETUGAS_ROLE = 'petugas';

    private const PETUGAS_D2D_ROLE = 'petugas-d2d';

    private const JASA_RAHARJA_ROLE = 'jasa_raharja';

    /** Role petugas lapangan (mobile); hanya guard web — API memakai token, bukan role guard api. */
    private const FIELD_OFFICER_ROLES = [self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE];

    private function resolveSamsatContext(?string $selectedSamsatId): array
    {
        if (empty($selectedSamsatId)) {
            return [
                'kabkota' => null,
                'lokasi_samsat' => null,
                'uptd_id' => null,
            ];
        }

        $samsat = SengSaamsat::query()
            ->select('id', 'id_wilayah_samsat', 'kabkota')
            ->where('id_wilayah_samsat', $selectedSamsatId)
            ->orWhere('id', $selectedSamsatId)
            ->first();

        if ($samsat) {
            $wilayah = trim((string) ($samsat->id_wilayah_samsat ?? ''));
            $lokasi = $wilayah !== '' ? $wilayah : (string) $samsat->id;

            return [
                'kabkota' => $samsat->kabkota ? (string) $samsat->kabkota : null,
                'lokasi_samsat' => $lokasi,
                'uptd_id' => $lokasi,
            ];
        }

        return [
            'kabkota' => null,
            'lokasi_samsat' => null,
            'uptd_id' => null,
        ];
    }

    private function getCurrentUserRoleName(): string
    {
        return strtolower((string) optional(Auth::user()->roles->first())->name);
    }

    private function roleIdsByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        return Role::query()
            ->whereIn('name', $names)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    private function roleLabel(string $roleName): string
    {
        return match (strtolower($roleName)) {
            self::PETUGAS_ROLE => 'Petugas',
            self::PETUGAS_D2D_ROLE => 'Petugas D2D',
            self::JASA_RAHARJA_ROLE => 'Jasa Raharja',
            'uppd' => 'UPPD',
            'uptd' => 'UPTD',
            'adminprov' => 'Admin Prov',
            default => ucwords(str_replace(['-', '_'], ' ', $roleName)),
        };
    }

    private function isFieldOfficerRoleName(string $roleName): bool
    {
        return in_array(strtolower($roleName), self::FIELD_OFFICER_ROLES, true);
    }

    private function isJasaRaharjaRoleName(string $roleName): bool
    {
        return strtolower($roleName) === self::JASA_RAHARJA_ROLE;
    }

    private function assignSelectedRole(User $user, Role $selectedRole): void
    {
        $roleName = strtolower((string) $selectedRole->name);

        if ($roleName === self::PETUGAS_ROLE) {
            $user->syncRoles([Role::findByName(self::PETUGAS_ROLE, 'web')]);

            return;
        }

        $user->syncRoles([Role::findByName($roleName, 'web')]);
    }

    private function getCurrentUserRoleId(): ?int
    {
        return Auth::user()->roles[0]->id ?? null;
    }

    /**
     * @return list<string>
     */
    private function getCreatableRoleNamesByCreator(string $creatorRoleName): array
    {
        // Catatan: role `petugas-d2d` TIDAK diizinkan dibuat oleh `kabkota`, `kecamatan`,
        // dan `kelurahan` — pembuatan akun petugas D2D hanya boleh dari UPTD/UPPD ke atas.
        return match ($creatorRoleName) {
            'uptd', 'uppd' => ['kabkota', 'kecamatan', 'kelurahan', self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE],
            'kabkota' => ['kecamatan', 'kelurahan', self::PETUGAS_ROLE],
            'kecamatan' => ['kelurahan', self::PETUGAS_ROLE],
            'kelurahan' => [self::PETUGAS_ROLE],
            default => ['admin', 'adminprov', 'uptd', 'uppd', 'kabkota', 'kecamatan', 'kelurahan', self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE, self::JASA_RAHARJA_ROLE],
        };
    }

    private function getAllowedRoleIdsByCreator(string $creatorRoleName): array
    {
        return $this->roleIdsByNames($this->getCreatableRoleNamesByCreator($creatorRoleName));
    }

    /**
     * Opsi filter role di halaman Users (diselaraskan dengan role yang boleh dikelola).
     *
     * @return list<array{name: string, label: string}>
     */
    private function getFilterableRolesForCreator(string $creatorRoleName): array
    {
        $names = $this->getCreatableRoleNamesByCreator($creatorRoleName);

        return collect($names)
            ->map(fn (string $name) => [
                'name' => $name,
                'label' => $this->roleLabel($name),
            ])
            ->values()
            ->all();
    }

    // public static function middleware(): array
    // {
    //     return [
    //         new Middleware('role:super-admin'),
    //     ];
    // }
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id ?? null;
        $userRoleId = $this->getCurrentUserRoleId();
        $userRoleName = $this->getCurrentUserRoleName();
        $userKotaId = $user->kota ?? null;
        $isUptdScope = in_array($userRoleName, ['uptd', 'uppd'], true);
        $isKabkotaScope = $userRoleName === 'kabkota';
        $isKecamatanScope = $userRoleName === 'kecamatan';
        $isKelurahanScope = $userRoleName === 'kelurahan';

        if ($request->ajax()) {
            // $users = User::select('*')->where('email != ');

            

            // $users = User::select('*')->where('email', '!=', 'superadmin@example.com')->get();

            // Ambil data user dasar
            // $usersQuery = User::where('email', '!=', 'prayogo.edwin@gmail.com');
            $usersQuery = User::whereNotIn('email', [
                'prayogo.edwin@gmail.com',
                'ucitech13@gmail.com'
            ]);

            // Petugas / petugas D2D hanya boleh melihat akun dirinya sendiri.
            if ($user->hasAnyRole(self::FIELD_OFFICER_ROLES)) {
                $usersQuery->where('id', $userId);
            }

            // UPTD/UPPD: tampilkan turunan di kabkota milik akun.
            if ($isUptdScope) {
                $usersQuery->where('kota', $userKotaId);
                $usersQuery->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['kabkota', 'kecamatan', 'kelurahan', self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE]);
                });
            }

            // Kabkota: tampilkan user kecamatan/kelurahan/petugas pada kabkota yang sama.
            // petugas-d2d sengaja tidak ditampilkan — bukan domain kabkota.
            if ($isKabkotaScope) {
                $usersQuery->where('kota', $userKotaId);
                $usersQuery->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['kecamatan', 'kelurahan', self::PETUGAS_ROLE]);
                });
            }

            // Kecamatan: tampilkan user kelurahan/petugas pada kecamatan yang sama.
            if ($isKecamatanScope) {
                $currentKecamatanSamsat = $user->kecamatan_samsat ?: $user->kecamatan;
                $kelurahanIdsInKecamatan = [];
                if (!empty($currentKecamatanSamsat)) {
                    $kelurahanIdsInKecamatan = SengWilayahKel::query()
                        ->where('id_kecamatan', $currentKecamatanSamsat)
                        ->pluck('id_kelurahan')
                        ->map(static fn ($id) => (string) $id)
                        ->all();
                }

                $usersQuery->where('kota', $userKotaId);
                $usersQuery->where(function ($query) use ($currentKecamatanSamsat, $kelurahanIdsInKecamatan) {
                    $query->where(function ($sub) use ($currentKecamatanSamsat) {
                        $sub->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'kelurahan');
                        });

                        if (!empty($currentKecamatanSamsat)) {
                            $sub->where('kecamatan_samsat', $currentKecamatanSamsat);
                        } else {
                            $sub->whereRaw('1 = 0');
                        }
                    })->orWhere(function ($sub) use ($kelurahanIdsInKecamatan) {
                        // petugas-d2d tidak ikut ditampilkan untuk scope kecamatan.
                        $sub->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', self::PETUGAS_ROLE);
                        });

                        if (!empty($kelurahanIdsInKecamatan)) {
                            $sub->whereIn('kelurahan_samsat', $kelurahanIdsInKecamatan);
                        } else {
                            $sub->whereRaw('1 = 0');
                        }
                    });
                });
            }

            // Kelurahan: tampilkan user petugas pada kelurahan yang sama.
            // petugas-d2d tidak ikut ditampilkan untuk scope kelurahan.
            if ($isKelurahanScope) {
                $currentKelurahanSamsat = $user->kelurahan_samsat ?: $user->kelurahan;
                $usersQuery->where('kota', $userKotaId);
                $usersQuery->whereHas('roles', function ($q) {
                    $q->where('name', self::PETUGAS_ROLE);
                });

                if (!empty($currentKelurahanSamsat)) {
                    $usersQuery->where('kelurahan_samsat', $currentKelurahanSamsat);
                } else {
                    $usersQuery->whereRaw('1 = 0');
                }
            }

            if ($request->filled('role_name')) {
                $roleName = strtolower((string) $request->role_name);
                $usersQuery->whereHas('roles', function ($q) use ($roleName) {
                    $q->whereRaw('LOWER(name) = ?', [$roleName]);
                });
            }

            // Untuk role tidak dikenali, sembunyikan data.
            if (
                !$isUptdScope
                && !$isKabkotaScope
                && !$isKecamatanScope
                && !$isKelurahanScope
                && !$user->hasAnyRole(self::FIELD_OFFICER_ROLES)
                && $userRoleId != 1
                && $userRoleId != 2
            ) {
                $usersQuery->where('kota', 'KuotaMaya');
            }

           

            $users = $usersQuery->with('roles')->get();

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('user_name', function ($user) {
                    $name = $user->name ? e($user->name) : 'N/A';
                    $roleName = strtolower((string) optional($user->roles->first())->name);

                    if ($roleName === self::JASA_RAHARJA_ROLE && ! empty($user->apikey)) {
                        return $name . '<br><small class="text-muted">API Key: ' . e($user->apikey) . '</small>';
                    }

                    return $name;
                })
                ->addColumn('email', function ($user) {
                    return $user->email ? $user->email : 'N/A';
                })
                ->addColumn('whatsapp', function ($user) {
                    return $user->whatsapp ? $user->whatsapp : 'N/A';
                })
                ->addColumn('roles', function ($user) {
                    // Menampilkan nama role
                    if ($user->roles && $user->roles->isNotEmpty()) {
                        return $user->roles->pluck('name')->join(', ');
                    }
                    return 'N/A'; // Jika tidak ada role
                })
                ->addColumn('options', function ($user) {
                    return '
                        <button class="btn btn-primary btn-sm" onclick="showEditModal(' . $user->id . ')">Edit</button>&nbsp;
                        <button class="btn btn-warning btn-sm" onclick="confirmResetPassword(' . $user->id . ')">ResetPass</button>&nbsp;
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(' . $user->id . ')">Delete</button>&nbsp;
                    ';
                })
                ->rawColumns(['user_name', 'options'])
                ->make(true);
        }

        $allowedRoleIds = $this->getAllowedRoleIdsByCreator($userRoleName);
        $roles = Role::select('id', 'name', 'guard_name')
            ->whereIn('id', $allowedRoleIds)
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) {
                $role->display_name = $this->roleLabel((string) $role->name);

                return $role;
            });

        if ($isUptdScope || $isKabkotaScope || $isKecamatanScope || $isKelurahanScope) {
            $kabkotas = ApiCacheManager::remember('admin:master:kabkota:role-scope:' . (string) $userKotaId, ApiCacheManager::masterTtl(), static function () use ($userKotaId) {
                return SengWilayah::select('*')
                    ->where('id', $userKotaId)
                    ->get();
            });
        } else {
            $kabkotas = ApiCacheManager::remember('admin:master:kabkota:all', ApiCacheManager::masterTtl(), static function () {
                return SengWilayah::select('*')
                    ->where('id_up', 33)
                    ->get();
            });
        }

        $samsats = ApiCacheManager::remember('admin:master:seng-samsat:all-full:v2', ApiCacheManager::masterTtl(), static function () {
            return SengSaamsat::select('id', 'id_wilayah_samsat', 'kabkota', 'lokasi', 'lokasi_singkat')
                ->orderBy('lokasi')
                ->get();
        });

       
        $filterRoles = $this->getFilterableRolesForCreator($userRoleName);

        return view('backend.users.index', compact('roles', 'kabkotas', 'samsats', 'userRoleName', 'filterRoles'));
    }

     public function ganti_password(Request $request)
    {
        $userId = Auth::user()->id ?? null;
        $userRoleId = Auth::user()->roles[0]->id ?? null;
        $userKotaId = Auth::user()->kota ?? null;
        $user = User::find($userId); // This returns a single user or null
        return view('backend.users.ganti', compact('user'));
    }

    public function ganti_password_action(Request $request, $id)
    {
        try {
            // Validasi untuk password
            // $request->validate([
            //     'password' => 'required|string|min:8|confirmed',
            // ]);

            $user = User::findOrFail($id);

            $user->update([
                'password' => bcrypt($request->password),
            ]);

            // return response()->json(['success' => true, 'message' => 'Password berhasil diupdate.']);
            return redirect()->route('user.ganti')->with('success', 'Password berhasil diupdate.');
        } catch (\Exception $e) {
            // return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            // For non-AJAX requests (fallback)
            return redirect()->route('user.ganti')->with('success', 'Password gagal diupdate.');
        }
    }

    // Method untuk menyimpan data user baru
    public function store(Request $request)
    {
        $creator = Auth::user();
        $creatorRoleName = $this->getCurrentUserRoleName();
        $allowedRoleIds = $this->getAllowedRoleIdsByCreator($creatorRoleName);
        $isUptdCreator = in_array($creatorRoleName, ['uptd', 'uppd'], true);
        $isKabkotaCreator = $creatorRoleName === 'kabkota';
        $isKecamatanCreator = $creatorRoleName === 'kecamatan';
        $isKelurahanCreator = $creatorRoleName === 'kelurahan';

        $selectedRole = Role::find((int) $request->role_id);
        $selectedRoleName = strtolower($selectedRole->name ?? '');
        $isJasaRaharjaRole = $this->isJasaRaharjaRoleName($selectedRoleName);

        if ($isJasaRaharjaRole) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,email',
                'password' => 'required|string|min:6',
                'api_key' => 'required|string|min:8|max:255',
                'role_id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()]);
            }

            if (! in_array((int) $request->role_id, $allowedRoleIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk membuat role ini.',
                ], 403);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->username,
                'whatsapp' => 'jr-' . \Illuminate\Support\Str::slug((string) $request->username),
                'password' => bcrypt($request->password),
                'apikey' => $request->api_key,
            ]);

            $this->assignSelectedRole($user, $selectedRole);

            return response()->json([
                'success' => true,
                'message' => 'Akun Jasa Raharja berhasil ditambahkan.',
            ]);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'whatsapp' => 'required|string|unique:users|max:15',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        if (!in_array((int) $request->role_id, $allowedRoleIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk membuat role ini.',
            ], 403);
        }

        $selectedRole = Role::find((int) $request->role_id);
        $selectedRoleName = strtolower($selectedRole->name ?? '');
        $isUptdRole = in_array($selectedRoleName, ['uptd', 'uppd'], true);
        $isFieldOfficerRole = $this->isFieldOfficerRoleName($selectedRoleName);
        $isSamsatBasedRole = in_array($selectedRoleName, ['kecamatan', 'kelurahan', self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE], true);
        $isKelurahanOrPetugasRole = in_array($selectedRoleName, ['kelurahan', self::PETUGAS_ROLE, self::PETUGAS_D2D_ROLE], true);
        $lokasiSamsat = $request->lokasi_samsat;

        if ($isUptdRole) {
            $validator = Validator::make($request->all(), [
                'uptd_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()]);
            }

            $samsatContext = $this->resolveSamsatContext((string) $request->uptd_id);

            $kota = $request->kabkota_id;
            $uptdId = $request->uptd_id;
            $lokasiSamsat = $request->uptd_id;
            $kecamatanKemendagri = $request->district_id;
            $kelurahanKemendagri = $request->kelurahan;

            if (!empty($samsatContext['kabkota'])) {
                $kota = $samsatContext['kabkota'];
                $lokasiSamsat = $samsatContext['lokasi_samsat'];
                $uptdId = $samsatContext['uptd_id'];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapping samsat tidak ditemukan. Pastikan data samsat memiliki kabkota yang valid.',
                ], 422);
            }
        } elseif($isSamsatBasedRole){
            $rules = [
                'kabkota_id' => 'required|string',
                'lokasi_samsat' => 'required|string',
                'kecamatan_samsat' => 'required|string',
            ];

            if ($isKelurahanOrPetugasRole) {
                $rules['kelurahan_samsat'] = 'required|string';
            }

            if ($isFieldOfficerRole) {
                $rules['rw'] = 'required|string';
                $rules['rt'] = 'required|string';
                $rules['alamat_lengkap'] = 'required|string';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()]);
            }

            $kota = $request->kabkota_id;
            $uptdId = null;
            $kecamatanKemendagri = $request->kecamatan_samsat;
            $kelurahanKemendagri = $isKelurahanOrPetugasRole ? $request->kelurahan_samsat : null;

            if ($isUptdCreator && (string) $kota !== (string) $creator->kota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun UPPD hanya boleh membuat user di kabkota samsat miliknya.',
                ], 403);
            }
        }else{
            $kota = $request->kabkota_id;
            $uptdId = $request->uptd_id;
            $kecamatanKemendagri = $request->district_id;
            $kelurahanKemendagri = $request->kelurahan;
        }

        if ($isUptdCreator || $isKabkotaCreator || $isKecamatanCreator || $isKelurahanCreator) {
            $kota = $creator->kota;
        }

        if (($isKecamatanCreator || $isKelurahanCreator) && !$isFieldOfficerRole) {
            $kecamatanKemendagri = $creator->kecamatan;
        }

        if ($isKelurahanCreator && !$isFieldOfficerRole) {
            $kelurahanKemendagri = $creator->kelurahan;
        }

        // Menyimpan data ke tabel users
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => bcrypt($request->email), // Set password default atau sesuai logika Anda
            'uptd_id' =>  $uptdId,
            'provinsi' =>  33,
            'kota' =>  $kota,
            'kecamatan' =>  $kecamatanKemendagri,
            'kelurahan' =>  $kelurahanKemendagri,
            'lokasi_samsat' => $lokasiSamsat,
            'kecamatan_samsat' => $request->kecamatan_samsat,
            'kelurahan_samsat' => $request->kelurahan_samsat,
            'rw' =>  $request->rw,
            'rt' =>  $request->rt,
            'alamat_lengkap' =>  $request->alamat_lengkap
        ]);

        if ($selectedRole) {
            $this->assignSelectedRole($user, $selectedRole);
        }


        return response()->json(['success' => true, 'message' => 'Tambah data berhasil, password default sesuai email akun: '.$request->email]);
    }

    public function getAdmin($id)
    {
        try {
            $user = User::with('roles:id,name')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            //Validasi untuk 'name'
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user = User::findOrFail($id);

            if ($user->email != $request->email) {
                // Validasi untuk 'email'
                $request->validate([
                    'email' => 'required|email|max:255|unique:users,email,' . $id, // Pastikan email unik kecuali untuk user ini
                ]);
            }


            if ($user->whatsapp != $request->whatsapp) {
                // Validasi untuk 'wa'
                $request->validate([
                    'whatsapp' => 'required|string|max:15', // Sesuaikan dengan format whatsapp
                ]);
            }

         


            // Cari admin berdasarkan ID
            // $admin = UserAdmin::findOrFail($id);

            // Perbarui data user terkait (user yang memiliki ID user_id di UserAdmin)
            // $user = $user->user;  // Ambil user yang terkait dengan admin ini
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
            ]);


            return response()->json(['success' => true, 'message' => 'update data berhasil.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function softdelete($id)
    {
        try {
            // Cari admin berdasarkan ID
            $user = User::findOrFail($id);

            // Set is_deleted = 1 untuk soft delete admin

            if ($user) {
                // Set is_deleted = 1 untuk soft delete user
                // $user->is_deleted = 1;
                $user->deleted_by = 1;
                $user->deleted_at = Carbon::now();
                $user->save();  // Simpan perubahan
                // $user->delete();
            }

            return response()->json(['success' => true, 'message' => 'Hapus data berhasil']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function resetPasswordToEmail($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->update([
                'password' => Hash::make($user->email),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset ke email user: ' . $user->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
