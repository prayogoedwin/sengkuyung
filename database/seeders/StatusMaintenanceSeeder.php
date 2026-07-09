<?php

namespace Database\Seeders;

use App\Models\StatusMaintenance;
use App\Support\MaintenanceManager;
use Illuminate\Database\Seeder;

class StatusMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        StatusMaintenance::query()->firstOrCreate([], [
            'maintenance' => false,
        ]);

        MaintenanceManager::syncFromDb();
    }
}
