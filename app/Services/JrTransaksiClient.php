<?php

namespace App\Services;

use App\Models\IntegrasiSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class JrTransaksiClient
{
    public function signature(string $secretKey, string $timestamp): string
    {
        return hash('sha256', $secretKey.':'.$timestamp);
    }

    public function transaksiUrl(string $baseUrl, string $tanggalPenetapan): string
    {
        $base = rtrim(trim($baseUrl), '/');

        if (! preg_match('#/jrdataTransaksi$#i', $base)) {
            $base .= '/jrdataTransaksi';
        }

        return $base.'/?tanggalPenetapan='.rawurlencode($tanggalPenetapan);
    }

    /**
     * @return array{url: string, timestamp: string, signature: string, response: Response}
     */
    public function fetch(IntegrasiSetting $setting, string $tanggalPenetapan): array
    {
        $timestamp = (string) time();
        $signature = $this->signature((string) $setting->secret_key, $timestamp);
        $url = $this->transaksiUrl((string) $setting->base_url, $tanggalPenetapan);

        $response = Http::timeout(60)
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
            ->withHeaders([
                'x-client-id' => (string) $setting->client_id,
                'x-timestamp' => $timestamp,
                'x-signature' => $signature,
                'Accept' => 'application/json',
            ])
            ->get($url);

        return [
            'url' => $url,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'response' => $response,
        ];
    }
}
