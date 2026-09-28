<?php

namespace App\Services;

use App\Models\IntegrasiSetting;
use App\Models\SengBayarPajak;
use App\Support\NopolFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SengBayarPajakApiImporter
{
    public const PAGE_SIZE = 100;

    private ?bool $nopolKeyExists = null;

    /**
     * @return array{inserted: int, skipped_duplicate: int, skipped_invalid: int, total_items: int}
     */
    public function importTanggal(string $tanggalPenetapan, ?int $userId): array
    {
        $page = 1;
        $inserted = 0;
        $skippedDuplicate = 0;
        $skippedInvalid = 0;
        $totalItems = 0;

        do {
            $result = $this->importPage($tanggalPenetapan, $page, $userId);
            $inserted += $result['inserted'];
            $skippedDuplicate += $result['skipped_duplicate'];
            $skippedInvalid += $result['skipped_invalid'];
            $totalItems = $result['total_items'];
            $page++;

            if ($page > 20000) {
                throw new RuntimeException('Halaman API melebihi batas.');
            }
        } while (! $result['done']);

        return [
            'inserted' => $inserted,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_invalid' => $skippedInvalid,
            'total_items' => $totalItems,
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     done: bool,
     *     page: int,
     *     total_pages: int,
     *     total_items: int,
     *     inserted: int,
     *     skipped_duplicate: int,
     *     skipped_invalid: int,
     *     message: string
     * }
     */
    public function importPage(string $tanggalPenetapan, int $page, ?int $userId): array
    {
        $tanggalPenetapan = Carbon::parse($tanggalPenetapan)->format('Y-m-d');
        $page = max(1, $page);

        $setting = IntegrasiSetting::query()->orderBy('id')->first();
        if ($setting === null) {
            throw new RuntimeException('Setting integrasi Jasa Raharja belum ada.');
        }

        $fetched = app(JrTransaksiClient::class)->fetchPage($setting, $tanggalPenetapan, $page, self::PAGE_SIZE);
        $response = $fetched['response'];

        if (! $response->successful()) {
            throw new RuntimeException('API Jasa Raharja gagal (HTTP '.$response->status().').');
        }

        $json = $response->json();
        if (! is_array($json) || ($json['success'] ?? false) !== true || ! is_array($json['data'] ?? null)) {
            throw new RuntimeException('Respons API Jasa Raharja tidak valid.');
        }

        /** @var array<int, mixed> $rows */
        $rows = $json['data'];
        $totalPages = max(1, (int) ($json['pagination']['totalPages'] ?? 1));
        $totalItems = (int) ($json['pagination']['totalItems'] ?? count($rows));

        $mapped = [];
        $skippedInvalid = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skippedInvalid++;

                continue;
            }

            $item = $this->mapRow($row, $tanggalPenetapan, $userId);
            if ($item === null) {
                $skippedInvalid++;

                continue;
            }

            $mapped[$item['nopol_']] = $item;
        }

        $skippedDuplicate = count($rows) - $skippedInvalid - count($mapped);
        if ($skippedDuplicate < 0) {
            $skippedDuplicate = 0;
        }

        if ($mapped !== []) {
            $existing = SengBayarPajak::query()
                ->whereDate('tgl_bayar', $tanggalPenetapan)
                ->whereIn('nopol_', array_keys($mapped))
                ->pluck('nopol_')
                ->all();

            foreach ($existing as $nopol) {
                $key = (string) $nopol;
                if (isset($mapped[$key])) {
                    unset($mapped[$key]);
                    $skippedDuplicate++;
                }
            }
        }

        if ($mapped !== []) {
            SengBayarPajak::query()->insert(array_values($mapped));
        }

        $inserted = count($mapped);
        $done = $page >= $totalPages || $rows === [];

        return [
            'success' => true,
            'done' => $done,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'inserted' => $inserted,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_invalid' => $skippedInvalid,
            'message' => 'Halaman '.$page.' dari '.$totalPages
                .'. Masuk: '.$inserted
                .'. Duplikat dilewati: '.$skippedDuplicate.'.',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row, string $tanggalPenetapan, ?int $userId): ?array
    {
        $rawNopol = trim((string) ($row['no_polisi'] ?? ''));
        $nopolNormalized = NopolFormatter::normalize($rawNopol);
        if ($nopolNormalized === '') {
            return null;
        }

        $nopolLama = trim((string) ($row['no_polisi_lama'] ?? ''));
        $now = now();

        $item = [
            'nopol' => $rawNopol,
            'nopol_' => $nopolNormalized,
            'nopol_lama' => $nopolLama !== '' ? $nopolLama : null,
            'tgl_bayar' => $tanggalPenetapan,
            'pkb_provinsi_jalan' => $this->amount($row['pkb_provinsi_jalan'] ?? null),
            'pkb_provinsi_tunggakan' => $this->amount($row['pkb_provinsi_tunggakan'] ?? null),
            'pkb_opsen_jalan' => $this->amount($row['pkb_opsen_jalan'] ?? null),
            'pkb_opsen_tunggakan' => $this->amount($row['pkb_opsen_tunggakan'] ?? null),
            'year' => (int) substr($tanggalPenetapan, 0, 4),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($this->nopolKeyColumnExists()) {
            $item['nopol_key'] = NopolFormatter::matchKey($nopolNormalized);
        }

        return $item;
    }

    private function nopolKeyColumnExists(): bool
    {
        return $this->nopolKeyExists ??= Schema::hasColumn('seng_bayar_pajak', 'nopol_key');
    }

    private function amount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
