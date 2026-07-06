<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate([
            'name' => 'role-access',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'jasa_raharja',
            'guard_name' => 'web',
        ]);

        $permission = Permission::where('name', 'role-access')
            ->where('guard_name', 'web')
            ->first();

        if ($permission && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Role::where('name', 'jasa_raharja')->where('guard_name', 'web')->delete();
    }
};
