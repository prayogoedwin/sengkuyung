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
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        // Membuat User Super Admin
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),  // Ganti dengan password yang lebih aman
        ]);

        // Memberikan Role Super Admin pada user
        $user->assignRole('super-admin');
    }
}

