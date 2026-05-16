<?php

namespace App\Http\Controllers;

use App\Models\SengPendataanKendaraanD2d;

class VerifikasiD2dController extends VerifikasiController
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function verifikasiRouteIndex(): string
    {
        return 'verifikasi-d2d.index';
    }

    protected function verifikasiRouteDetail(): string
    {
        return 'verifikasi-d2d-detail.index';
    }

    protected function verifikasiRouteStatus(): string
    {
        return 'verifikasi-d2d.status';
    }

    protected function verifikasiViewIndex(): string
    {
        return 'backend.verifikasis-d2d.index';
    }

    protected function verifikasiViewShow(): string
    {
        return 'backend.verifikasis-d2d.show';
    }

    protected function verifikasiPageTitle(): string
    {
        return 'Verifikasi D2D';
    }
}
