<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\HandlesApiDataTertagih;
use App\Models\DataTertagih;

class DataTertagihController extends Controller
{
    use HandlesApiDataTertagih;

    protected function dataTertagihModelClass(): string
    {
        return DataTertagih::class;
    }

    protected function dataTertagihWilayahValidationMessages(): array
    {
        return [
            'lokasi_samsat.required' => 'Wilayah samsat tidak lengkap pada profil akun petugas.',
            'kecamatan_samsat.required' => 'Kecamatan samsat tidak lengkap pada profil akun petugas.',
            'kelurahan_samsat.required' => 'Kelurahan samsat tidak lengkap pada profil akun petugas.',
        ];
    }
}
