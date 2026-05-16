<?php

namespace App\Http\Controllers\API;

use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraanD2d;

class SengPendataanKendaraanD2dController extends SengPendataanKendaraanController
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function findPendataan(int $id): ?SengPendataanKendaraanD2d
    {
        return $this->pendataanModelClass()::find($id);
    }
}
