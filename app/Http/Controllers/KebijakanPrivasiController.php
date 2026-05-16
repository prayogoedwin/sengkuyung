<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KebijakanPrivasiController extends Controller
{
    public static function content(): array
    {
        $policy = require resource_path('data/kebijakan-privasi.php');
        $policy['url'] = url('/kebijakan-privasi');

        return $policy;
    }

    public function show(): View
    {
        return view('kebijakan-privasi', [
            'policy' => self::content(),
        ]);
    }

    public function api(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Kebijakan privasi berhasil dimuat',
            'data' => self::content(),
        ]);
    }
}
