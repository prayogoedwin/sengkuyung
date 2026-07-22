<?php

namespace App\Support;

/**
 * Normalisasi nopol Indonesia ke format AA-1234-XYZ.
 */
class NopolFormatter
{
    public static function normalize(string $rawValue): string
    {
        $cleaned = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($rawValue)) ?? '');

        if ($cleaned === '') {
            return '';
        }

        // Contoh: H4878XA → H-4878-XA, AB1234CD → AB-1234-CD, H1847Z → H-1847-Z
        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $cleaned, $matches) === 1) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }

        return $cleaned;
    }
}
