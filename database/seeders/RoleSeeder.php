<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar permission yang tersedia di kedua guard
        $permissions = [
            'role-access'
        ];

        foreach ($permissions as $permissionName) {
            foreach (['web', 'api'] as $guard) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guard
                ]);
            }
        }

        // Daftar role dan guardnya
        $roles = [
            'super-admin' => ['web'],
            'admin'       => ['web'],
            'uptd'        => ['web'],
            'kabkota'     => ['web'],
            'kecamatan'   => ['web'],
            'kelurahan'   => ['web'],
            'petugas'     => ['web', 'api'], // Petugas bisa di Web & API
        ];

        foreach ($roles as $roleName => $guards) {
            foreach ($guards as $guard) {
                // Buat role dengan guard yang sesuai
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard
                ]);

                // Ambil permission yang sesuai dengan guard
                $permission = Permission::where('name', 'role-access')
                    ->where('guard_name', $guard)
                    ->first();

                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
