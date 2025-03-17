<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SengWilayah;

class WilayahController extends Controller
{
    public function getDistricts(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');

        if (!$kabkotaId) {
            return response()->json(['success' => false, 'message' => 'Kabkota ID is required.'], 400);
        }

        $districts = SengWilayah::where('id_up', $kabkotaId)->get();

        return response()->json(['success' => true, 'districts' => $districts]);
    }
}
