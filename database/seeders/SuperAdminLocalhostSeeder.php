<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminLocalhostSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::query()->updateOrCreate(
            ['email' => 'superadmin@localhost.com'],
            [
                'name' => 'Super Admin Localhost',
                'password' => 'Serdadu45!~',
            ]
        );

        $user->syncRoles([Role::findByName('super-admin', 'web')]);
    }
}
