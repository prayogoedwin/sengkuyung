<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\HandlesJrVerifikasi;
use App\Http\Controllers\Controller;
use App\Models\SengPendataanKendaraan;
use Illuminate\Http\Request;

class JrVerifikasiController extends Controller
{
    use HandlesJrVerifikasi;

    public function index(Request $request)
    {
        return $this->paginateJrVerifikasi($request);
    }

    protected function jrVerifikasiModelClass(): string
    {
        return SengPendataanKendaraan::class;
    }
}
