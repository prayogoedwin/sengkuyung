<?php

namespace App\Http\Controllers;

use App\Models\SengPendataanKendaraanD2d;
use App\Models\DataTertagihD2d;
use Illuminate\Http\Request;
class PelaporanD2dController extends PelaporanController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!RekapD2dController::userCanAccessRekapPelaporanD2d()) {
                abort(403, 'Akses Pelaporan D2D ditolak.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $this->applyUppdScope($request, $user);

        return parent::index($request);
    }

    public function pelaporanCsv(Request $request)
    {
        $user = auth()->user();
        $this->applyUppdScope($request, $user);

        return parent::pelaporanCsv($request);
    }

    public function pelaporanExcel(Request $request)
    {
        $user = auth()->user();
        $this->applyUppdScope($request, $user);

        return parent::pelaporanExcel($request);
    }

    public function pelaporanPdf(Request $request)
    {
        $user = auth()->user();
        $this->applyUppdScope($request, $user);

        return parent::pelaporanPdf($request);
    }

    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function pelaporanViewName(): string
    {
        return 'backend.pelaporan-d2d.index';
    }

    protected function pelaporanRouteCsv(): string
    {
        return 'pelaporan-d2d.csv';
    }

    protected function pelaporanRouteExcel(): string
    {
        return 'pelaporan-d2d.excel';
    }

    protected function pelaporanRoutePdf(): string
    {
        return 'pelaporan-d2d.pdf';
    }

    protected function pelaporanRouteIndex(): string
    {
        return 'pelaporan-d2d.index';
    }

    protected function exportFilenamePrefix(): string
    {
        return 'd2d_';
    }
}
