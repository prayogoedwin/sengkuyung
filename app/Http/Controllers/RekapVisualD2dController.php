<?php

namespace App\Http\Controllers;

use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraanD2d;

class RekapVisualD2dController extends RekapVisualController
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function viewName(): string
    {
        return 'backend.rekap-visual.index';
    }

    protected function routeIndex(): string
    {
        return 'rekap-visual-d2d.index';
    }

    protected function routeStats(): string
    {
        return 'rekap-visual-d2d.stats';
    }

    protected function routeMap(): string
    {
        return 'rekap-visual-d2d.map';
    }

    protected function routeSibling(): string
    {
        return 'rekap-visual.index';
    }

    protected function pageTitle(): string
    {
        return 'REKAP VISUAL SENGKUYUNG DOOR TO DOOR';
    }

    protected function channelLabel(): string
    {
        return 'D2D';
    }

    protected function isD2d(): bool
    {
        return true;
    }

    protected function cachePrefix(): string
    {
        return 'admin:rekap-visual-d2d:v6:';
    }
}
