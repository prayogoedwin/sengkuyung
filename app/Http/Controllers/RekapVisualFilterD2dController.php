<?php

namespace App\Http\Controllers;

use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraanD2d;

class RekapVisualFilterD2dController extends RekapVisualFilterController
{
    protected function pendataanTable(): string
    {
        return (new SengPendataanKendaraanD2d())->getTable();
    }

    protected function tertagihTable(): string
    {
        return (new DataTertagihD2d())->getTable();
    }

    protected function routeIndex(): string
    {
        return 'rekap-visual-filter-d2d.index';
    }

    protected function routeStats(): string
    {
        return 'rekap-visual-filter-d2d.stats';
    }

    protected function routeBreakdown(): string
    {
        return 'rekap-visual-filter-d2d.breakdown';
    }

    protected function routeMap(): string
    {
        return 'rekap-visual-filter-d2d.map';
    }

    protected function routeOptions(): string
    {
        return 'rekap-visual-filter-d2d.options';
    }

    protected function routeSibling(): string
    {
        return 'rekap-visual-filter.index';
    }

    protected function pageTitle(): string
    {
        return 'REKAP VISUAL FILTER DOOR TO DOOR';
    }

    protected function channelLabel(): string
    {
        return 'D2D';
    }

    protected function isD2d(): bool
    {
        return true;
    }

    protected function cacheNamespace(): string
    {
        return 'rvf:standalone:v2:d2d:';
    }
}
