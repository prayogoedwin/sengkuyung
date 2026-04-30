<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use App\Models\SengWilayah;
use App\Models\WilayahSamsat;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// class UserController extends Controller implements HasMiddleware
class UserController extends Controller 
{
    private function getCurrentUserRoleId(): ?int
    {
        return Auth::user()->roles[0]->id ?? null;
    }

    private function getAllowedRoleIdsByCreator(?int $creatorRoleId): array
    {
        return match ($creatorRoleId) {
            4 => [5, 6, 7], // kabkota -> kecamatan, kelurahan, petugas
            5 => [6, 7],    // kecamatan -> kelurahan, petugas
            6 => [7],       // kelurahan -> petugas
            default => [2, 3, 4, 5, 6, 7],
        };
    }

    // public static function middleware(): array
    // {
    //     return [
    //         new Middleware('role:super-admin'),
    //     ];
    // }
    public function index(Request $request)
    {

        $userId = Auth::user()->id ?? null;
        $userRoleId = $this->getCurrentUserRoleId();
        $userKotaId = Auth::user()->kota ?? null;

        if ($request->ajax()) {
            // $users = User::select('*')->where('email != ');

            

            // $users = User::select('*')->where('email', '!=', 'superadmin@example.com')->get();

            // Ambil data user dasar
            // $usersQuery = User::where('email', '!=', 'prayogo.edwin@gmail.com');
            $usersQuery = User::whereNotIn('email', [
                'prayogo.edwin@gmail.com',
                'ucitech13@gmail.com'
            ]);

            // Jika role-nya 4, filter berdasarkan kota milik user
            if ($userRoleId == 7) {
                $usersQuery->where('id', $userId);
            }

            // Jika role-nya 4, filter berdasarkan kota milik user
            if ($userRoleId == 4) {
                $usersQuery->where('kota', $userKotaId);
                $usersQuery->whereHas('roles', function ($q) {
                    $q->where('id', 7);
                });
            }

             // Jika role-nya 4, filter berdasarkan kota milik user
            if ($userRoleId != 4 && $userRoleId != 1 && $userRoleId != 2) {
                $usersQuery->where('kota', 'KuotaMaya');
            }

           

            $users = $usersQuery->get();

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('user_name', function ($user) {
                    return $user->name ? $user->name : 'N/A';
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
                ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                ->make(true);
        }

        $allowedRoleIds = $this->getAllowedRoleIdsByCreator($userRoleId);
        $roles = Role::select('id', 'name')
            ->whereIn('id', $allowedRoleIds)
            ->get();

        if (in_array($userRoleId, [4, 5, 6], true)) {
            $kabkotas = SengWilayah::select('*')
                ->where('id', $userKotaId)
                ->get();
        } else {
            $kabkotas = SengWilayah::select('*')
                ->where('id_up', 33)
                ->get();
        }

        $samsats = WilayahSamsat::select('*')->get();

       
        return view('backend.users.index',  compact('roles', 'kabkotas', 'samsats'));
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
        $creatorRoleId = $this->getCurrentUserRoleId();
        $allowedRoleIds = $this->getAllowedRoleIdsByCreator($creatorRoleId);

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
        $isPetugasRole = $selectedRoleName === 'petugas';
        $isSamsatBasedRole = in_array($selectedRoleName, ['kecamatan', 'kelurahan', 'petugas'], true);
        $isKelurahanOrPetugasRole = in_array($selectedRoleName, ['kelurahan', 'petugas'], true);
        $lokasiSamsat = $request->lokasi_samsat;

        if ($isUptdRole) {
            $validator = Validator::make($request->all(), [
                'uptd_id' => 'required|exists:wilayah_samsat,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()]);
            }

            $samsat = WilayahSamsat::find($request->uptd_id);

            $kota = $request->kabkota_id;
            $uptdId = $request->uptd_id;
            $lokasiSamsat = $request->uptd_id;
            $kecamatanKemendagri = $request->district_id;
            $kelurahanKemendagri = $request->kelurahan;

            if ($samsat) {
                $kota = $samsat->kabkota;
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

            if ($isPetugasRole) {
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
        }else{
            $kota = $request->kabkota_id;
            $uptdId = $request->uptd_id;
            $kecamatanKemendagri = $request->district_id;
            $kelurahanKemendagri = $request->kelurahan;
        }

        if (in_array($creatorRoleId, [4, 5, 6], true)) {
            $kota = $creator->kota;
        }

        if (in_array($creatorRoleId, [5, 6], true) && !$isPetugasRole) {
            $kecamatanKemendagri = $creator->kecamatan;
        }

        if ($creatorRoleId === 6 && !$isPetugasRole) {
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

        // Menambahkan role ke user
        if ($selectedRole) {
            $user->assignRole($selectedRole);
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
