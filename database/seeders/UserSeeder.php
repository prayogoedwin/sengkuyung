<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Membuat Role Super Admin jika belum ada
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $petugasWebRole = Role::firstOrCreate(['name' => 'petugas', 'guard_name' => 'web']);
        $petugasApiRole = Role::firstOrCreate(['name' => 'petugas', 'guard_name' => 'api']);

        // Membuat User Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'), // Ganti dengan password yang lebih aman
        ]);
        $superAdmin->assignRole($superAdminRole);

        // Membuat User Petugas
        $petugas = User::create([
            'name' => 'Petugas',
            'email' => 'petugas@example.com',
            'password' => bcrypt('password123'), // Ganti dengan password yang lebih aman
        ]);
        $petugas->assignRole($petugasWebRole, $petugasApiRole);
    }
}

