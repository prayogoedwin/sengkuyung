<?php

namespace App\Http\Controllers\API;

use App\Models\SengPendataanKendaraanD2d;

class JrVerifikasiD2dController extends JrVerifikasiController
{
    protected function jrVerifikasiModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }
}
