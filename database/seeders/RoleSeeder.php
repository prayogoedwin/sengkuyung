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
        // Membuat permission jika belum ada
        $permission = Permission::firstOrCreate(['name' => 'role-access']);
        
        // Membuat role jika belum ada dan memberi permission ke role
        $roles = [
            'super-admin',
            'admin',
            'kabkota',
            'kecamatan',
            'kelurahan',
            'rw',
            'rt'
        ];

        foreach ($roles as $roleName) {
            // Membuat role baru jika belum ada
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Menambahkan permission ke role
            $role->givePermissionTo($permission);
        }
    }
}