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
        $base = preg_replace('#/(jr)?dataTransaksi$#i', '', $base) ?? $base;
        $base = rtrim($base, '/');

        return $base.'/dataTransaksi/?tanggalPenetapan='.rawurlencode($tanggalPenetapan);
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

    /**
     * @return array{url: string, timestamp: string, signature: string, response: Response}
     */
    public function fetchPage(IntegrasiSetting $setting, string $tanggalPenetapan, int $page = 1, int $pageSize = 100): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $timestamp = (string) time();
        $signature = $this->signature((string) $setting->secret_key, $timestamp);
        $url = $this->transaksiUrl((string) $setting->base_url, $tanggalPenetapan);
        $url .= (str_contains($url, '?') ? '&' : '?').http_build_query([
            'page' => $page,
            'pageSize' => $pageSize,
        ], '', '&', PHP_QUERY_RFC3986);

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
