<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Petugas hanya guard web (id 7). Role id 8 diubah menjadi petugas-d2d (web).
     * User yang punya role petugas api lama (8) tanpa role 7: dapat role petugas web.
     * User yang punya role 7 + 8: hapus pivot role 8 (duplikat api).
     */
    public function up(): void
    {
        $petugasWebRoleId = DB::table('roles')
            ->where('name', 'petugas')
            ->where('guard_name', 'web')
            ->value('id');

        $oldApiPetugasRoleId = DB::table('roles')
            ->where('name', 'petugas')
            ->where('guard_name', 'api')
            ->value('id');

        if ($petugasWebRoleId && $oldApiPetugasRoleId) {
            $onlyApiUserIds = DB::table('model_has_roles')
                ->where('role_id', $oldApiPetugasRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->whereNotIn('model_id', function ($q) use ($petugasWebRoleId) {
                    $q->select('model_id')
                        ->from('model_has_roles')
                        ->where('role_id', $petugasWebRoleId)
                        ->where('model_type', 'App\\Models\\User');
                })
                ->pluck('model_id');

            foreach ($onlyApiUserIds as $userId) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $petugasWebRoleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }

            DB::table('model_has_roles')
                ->where('role_id', $oldApiPetugasRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', function ($q) use ($petugasWebRoleId) {
                    $q->select('model_id')
                        ->from('model_has_roles')
                        ->where('role_id', $petugasWebRoleId)
                        ->where('model_type', 'App\\Models\\User');
                })
                ->delete();
        }

        DB::table('roles')
            ->where('name', 'petugas')
            ->where('guard_name', 'api')
            ->update([
                'name' => 'petugas-d2d',
                'guard_name' => 'web',
                'updated_at' => now(),
            ]);

        DB::table('roles')
            ->where('id', 8)
            ->where('name', '!=', 'petugas-d2d')
            ->update([
                'name' => 'petugas-d2d',
                'guard_name' => 'web',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'petugas-d2d')
            ->where('guard_name', 'web')
            ->update([
                'name' => 'petugas',
                'guard_name' => 'api',
                'updated_at' => now(),
            ]);
    }
};
