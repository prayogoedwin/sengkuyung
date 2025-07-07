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

// class UserController extends Controller implements HasMiddleware
class UserController extends Controller 
{
    // public static function middleware(): array
    // {
    //     return [
    //         new Middleware('role:super-admin'),
    //     ];
    // }
    public function index(Request $request)
    {

        $userId = Auth::user()->id ?? null;
        $userRoleId = Auth::user()->roles[0]->id ?? null;
        $userKotaId = Auth::user()->kota ?? null;

        if ($request->ajax()) {
            // $users = User::select('*')->where('email != ');

            

            // $users = User::select('*')->where('email', '!=', 'superadmin@example.com')->get();

            // Ambil data user dasar
            $usersQuery = User::where('email', '!=', 'superadmin@example.com');

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
                        <button class="btn btn-primary btn-sm" onclick="showEditModal(' . $user->id . ')">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(' . $user->id . ')">Delete</button>
                    ';
                })
                ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                ->make(true);
        }

        if ($userRoleId == 4) {

            // Ambil data roles untuk dikirim ke view
            $roles = Role::select('id', 'name')
            ->whereIn('id', [7])
            ->get();

            $kabkotas = SengWilayah::select('*')
            ->where('id', $userKotaId)
            ->get();

        }else{

            // Ambil data roles untuk dikirim ke view
            $roles = Role::select('id', 'name')
            ->whereIn('id', [2, 3, 4,7])
            ->get();

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

        if($request->role_id == 3){
            $wilayah = WilayahSamsat::find($request->uptd_id);
            $kota = $wilayah->kabkota;
        }else{
            $kota = $request->kabkota_id;
        }

        // Menyimpan data ke tabel users
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => bcrypt($request->email), // Set password default atau sesuai logika Anda
            'uptd_id' =>  $request->uptd_id,
            'provinsi' =>  33,
            'kota' =>  $kota,
            'kecamatan' =>  $request->district_id,
            'kelurahan' =>  $request->kelurahan,
            'rw' =>  $request->rw,
            'rt' =>  $request->rt,
            'alamat_lengkap' =>  $request->alamat_lengkap
        ]);

        // Menambahkan role ke user
        $role = Role::find($request->role_id);
        $user->assignRole($role);


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
}
