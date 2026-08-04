<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use ZipArchive;

/**
 * Konversi XLSX besar ke CSV secara streaming + resumable (tanpa PhpSpreadsheet load penuh).
 */
class XlsxStreamToCsvConverter
{
    public const CONVERT_CHUNK_ROWS = 40000;

    /**
     * Ekstrak sheet + sharedStrings dari XLSX ke folder kerja.
     *
     * @return array{sheet_path: string, shared_strings_path: string}
     */
    public function extract(string $xlsxPath, string $workDir): array
    {
        if (!is_dir($workDir) && !mkdir($workDir, 0755, true) && !is_dir($workDir)) {
            throw new \RuntimeException('Gagal membuat folder kerja konversi.');
        }

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            throw new \RuntimeException('File XLSX tidak valid / tidak dapat dibuka.');
        }

        $sheetName = $this->resolveSheetPath($zip);
        $ssName = 'xl/sharedStrings.xml';

        if ($zip->locateName($sheetName) === false) {
            $zip->close();
            throw new \RuntimeException('Worksheet tidak ditemukan di dalam file Excel.');
        }

        $entries = [$sheetName];
        if ($zip->locateName($ssName) !== false) {
            $entries[] = $ssName;
        }

        if (!$zip->extractTo($workDir, $entries)) {
            $zip->close();
            throw new \RuntimeException('Gagal mengekstrak isi Excel ke disk.');
        }
        $zip->close();

        $sheetPath = $workDir . '/' . $sheetName;
        $ssPath = $workDir . '/' . $ssName;

        if (!is_file($sheetPath)) {
            throw new \RuntimeException('File worksheet hasil ekstrak tidak ditemukan.');
        }

        return [
            'sheet_path' => $sheetPath,
            'shared_strings_path' => is_file($ssPath) ? $ssPath : '',
        ];
    }

    /**
     * Bangun indeks offset shared strings (lookup cepat tanpa load semua ke memori).
     */
    public function buildSharedStringIndex(string $sharedStringsPath, string $indexPath): int
    {
        if ($sharedStringsPath === '' || !is_file($sharedStringsPath)) {
            file_put_contents($indexPath, '');

            return 0;
        }

        $fh = fopen($sharedStringsPath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('Gagal membaca sharedStrings.xml.');
        }

        $indexFh = fopen($indexPath, 'wb');
        if ($indexFh === false) {
            fclose($fh);
            throw new \RuntimeException('Gagal membuat indeks shared strings.');
        }

        $count = 0;
        $buffer = '';
        $carry = '';

        try {
            while (!feof($fh)) {
                $chunk = fread($fh, 1024 * 1024);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                $buffer = $carry . $chunk;
                $carry = '';
                $offsetBase = ftell($fh) - strlen($buffer);

                $pos = 0;
                $len = strlen($buffer);
                while ($pos < $len) {
                    $start = strpos($buffer, '<si', $pos);
                    if ($start === false) {
                        $carry = substr($buffer, $pos);
                        break;
                    }

                    // Pastikan tag <si> / <si ...>
                    $next = $buffer[$start + 3] ?? '';
                    if ($next !== '>' && $next !== ' ' && $next !== '/' && $next !== "\t" && $next !== "\n" && $next !== "\r") {
                        $pos = $start + 3;
                        continue;
                    }

                    $end = strpos($buffer, '</si>', $start);
                    if ($end === false) {
                        $carry = substr($buffer, $start);
                        break;
                    }

                    $absolute = $offsetBase + $start;
                    fwrite($indexFh, pack('N', $absolute));
                    $count++;
                    $pos = $end + 5;
                }
            }
        } finally {
            fclose($fh);
            fclose($indexFh);
        }

        return $count;
    }

    /**
     * Konversi sebagian baris sheet → append ke CSV.
     *
     * @param  array{sheet_path: string, shared_strings_path: string, index_path: string, csv_path: string, byte_offset: int, rows_done: int, date_col: string}  $state
     * @return array{done: bool, byte_offset: int, rows_done: int, rows_written: int, date_col: string}
     */
    public function convertChunk(array $state, int $maxRows = self::CONVERT_CHUNK_ROWS): array
    {
        $sheetPath = $state['sheet_path'];
        $ssPath = (string) ($state['shared_strings_path'] ?? '');
        $indexPath = (string) ($state['index_path'] ?? '');
        $csvPath = $state['csv_path'];
        $byteOffset = (int) ($state['byte_offset'] ?? 0);
        $rowsDone = (int) ($state['rows_done'] ?? 0);
        $dateCol = (string) ($state['date_col'] ?? '');

        $sheetFh = fopen($sheetPath, 'rb');
        if ($sheetFh === false) {
            throw new \RuntimeException('Gagal membuka worksheet untuk konversi.');
        }

        if ($byteOffset > 0) {
            fseek($sheetFh, $byteOffset);
        }

        $csvFh = fopen($csvPath, $rowsDone === 0 ? 'w' : 'a');
        if ($csvFh === false) {
            fclose($sheetFh);
            throw new \RuntimeException('Gagal membuka file CSV sementara.');
        }

        $ssFh = ($ssPath !== '' && is_file($ssPath)) ? fopen($ssPath, 'rb') : false;
        $indexFh = ($indexPath !== '' && is_file($indexPath)) ? fopen($indexPath, 'rb') : false;

        $buffer = '';
        $rowsWritten = 0;
        $done = false;

        try {
            while ($rowsWritten < $maxRows) {
                if (strlen($buffer) < 256 * 1024 && !feof($sheetFh)) {
                    $chunk = fread($sheetFh, 512 * 1024);
                    if ($chunk !== false && $chunk !== '') {
                        $buffer .= $chunk;
                    }
                }

                if (!preg_match('/<row\b[^>]*(?:\/>|>.*?<\/row>)/s', $buffer, $match, PREG_OFFSET_CAPTURE)) {
                    if (feof($sheetFh)) {
                        $done = true;
                        break;
                    }
                    // Belum lengkap — baca lagi
                    if (strlen($buffer) > 8 * 1024 * 1024) {
                        throw new \RuntimeException('Baris Excel terlalu besar untuk diproses.');
                    }
                    continue;
                }

                $rowXml = $match[0][0];
                $start = (int) $match[0][1];
                $buffer = substr($buffer, $start + strlen($rowXml));

                $cells = $this->parseRowCells($rowXml, $ssFh, $indexFh);
                $rowsDone++;
                $rowsWritten++;

                if ($rowsDone === 1) {
                    $dateCol = $this->detectDateColumn($cells);
                    fputcsv($csvFh, $this->cellsToOrderedValues($cells));
                    continue;
                }

                $values = $this->cellsToOrderedValues($cells);
                if ($dateCol !== '' && isset($cells[$dateCol]) && is_numeric($cells[$dateCol])) {
                    $colIndex = ord($dateCol) - ord('A');
                    if (isset($values[$colIndex])) {
                        try {
                            $values[$colIndex] = ExcelDate::excelToDateTimeObject((float) $cells[$dateCol])->format('Y-m-d');
                        } catch (\Throwable) {
                            // biarkan nilai asli
                        }
                    }
                }

                fputcsv($csvFh, $values);
            }

            if (!$done && feof($sheetFh) && trim($buffer) === '') {
                $done = true;
            } elseif (!$done && feof($sheetFh) && !preg_match('/<row\b/i', $buffer)) {
                $done = true;
            }
        } finally {
            $newOffset = ftell($sheetFh) - strlen($buffer);
            if ($newOffset < 0) {
                $newOffset = 0;
            }

            fclose($sheetFh);
            fclose($csvFh);
            if (is_resource($ssFh)) {
                fclose($ssFh);
            }
            if (is_resource($indexFh)) {
                fclose($indexFh);
            }
        }

        return [
            'done' => $done,
            'byte_offset' => $newOffset,
            'rows_done' => $rowsDone,
            'rows_written' => $rowsWritten,
            'date_col' => $dateCol,
        ];
    }

    public function cleanupWorkDir(string $workDir): void
    {
        if ($workDir === '' || !is_dir($workDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($workDir);
    }

    private function resolveSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rid = null;
        if (preg_match('/<sheet\b[^>]*r:id="(rId\d+)"/i', $workbook, $m)) {
            $rid = $m[1];
        }

        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rid && $rels !== false && preg_match(
            '/Id="' . preg_quote($rid, '/') . '"[^>]*Target="([^"]+)"/i',
            $rels,
            $tm
        )) {
            $target = ltrim(str_replace('\\', '/', $tm[1]), '/');
            if (!str_starts_with($target, 'xl/')) {
                $target = 'xl/' . $target;
            }

            return $target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  resource|false  $ssFh
     * @param  resource|false  $indexFh
     * @return array<string, string>
     */
    private function parseRowCells(string $rowXml, $ssFh, $indexFh): array
    {
        $cells = [];

        if (!preg_match_all('/<c\b([^>]*)>(.*?)<\/c>|<c\b([^>]*)\/>/s', $rowXml, $matches, PREG_SET_ORDER)) {
            return $cells;
        }

        foreach ($matches as $match) {
            $attrs = $match[1] !== '' ? $match[1] : ($match[3] ?? '');
            $body = $match[2] ?? '';

            if (!preg_match('/\br="([A-Z]+)(\d+)"/', $attrs, $rm)) {
                continue;
            }
            $col = $rm[1];

            $type = '';
            if (preg_match('/\bt="([^"]+)"/', $attrs, $tm)) {
                $type = $tm[1];
            }

            $value = '';
            if ($type === 'inlineStr') {
                if (preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $body, $tm)) {
                    $value = html_entity_decode(implode('', $tm[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            } elseif (preg_match('/<v>([^<]*)<\/v>/', $body, $vm)) {
                $raw = $vm[1];
                if ($type === 's' && $ssFh && $indexFh) {
                    $value = $this->lookupSharedString($ssFh, $indexFh, (int) $raw);
                } else {
                    $value = html_entity_decode($raw, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }

            $cells[$col] = $value;
        }

        return $cells;
    }

    /**
     * @param  resource  $ssFh
     * @param  resource  $indexFh
     */
    private function lookupSharedString($ssFh, $indexFh, int $index): string
    {
        if ($index < 0) {
            return '';
        }

        fseek($indexFh, $index * 4);
        $packed = fread($indexFh, 4);
        if ($packed === false || strlen($packed) < 4) {
            return '';
        }

        $offset = unpack('N', $packed)[1];
        fseek($ssFh, $offset);
        $chunk = fread($ssFh, 8192);
        if ($chunk === false) {
            return '';
        }

        $end = strpos($chunk, '</si>');
        if ($end === false) {
            $chunk .= fread($ssFh, 65536) ?: '';
            $end = strpos($chunk, '</si>');
        }
        if ($end === false) {
            return '';
        }

        $si = substr($chunk, 0, $end);
        if (!preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $si, $tm)) {
            return '';
        }

        return html_entity_decode(implode('', $tm[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @param  array<string, string>  $cells
     */
    private function detectDateColumn(array $cells): string
    {
        foreach ($cells as $col => $value) {
            $normalized = strtoupper(trim($value));
            if ($normalized === 'TGL_BAYAR' || $normalized === 'TANGGAL_BAYAR') {
                return $col;
            }
        }

        return 'C';
    }

    /**
     * @param  array<string, string>  $cells
     * @return list<string>
     */
    private function cellsToOrderedValues(array $cells): array
    {
        $values = [];
        for ($col = 'A'; $col <= 'G'; $col++) {
            $values[] = $cells[$col] ?? '';
        }

        return $values;
    }
}
