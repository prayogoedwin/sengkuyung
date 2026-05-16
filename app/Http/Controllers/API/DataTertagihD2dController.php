<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\HandlesApiDataTertagih;
use App\Models\DataTertagihD2d;

class DataTertagihD2dController extends Controller
{
    use HandlesApiDataTertagih;

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function dataTertagihWilayahValidationMessages(): array
    {
        return [
            'lokasi_samsat.required' => 'Wilayah samsat tidak lengkap pada profil akun petugas D2D.',
            'kecamatan_samsat.required' => 'Kecamatan samsat tidak lengkap pada profil akun petugas D2D.',
            'kelurahan_samsat.required' => 'Kelurahan samsat tidak lengkap pada profil akun petugas D2D.',
        ];
    }

    protected function pendataanModelClassForTertagihCheck(): string
    {
        return \App\Models\SengPendataanKendaraanD2d::class;
    }
}
