<?php

namespace App\Support;

class MoneyShortFormatter
{
    public static function format(int|float|null $amount): string
    {
        $n = (float) ($amount ?? 0);

        if (abs($n) >= 1_000_000_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000_000_000, 2, ',', ''), '0'), ',') . ' T';
        }

        if (abs($n) >= 1_000_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000_000, 2, ',', ''), '0'), ',') . ' M';
        }

        if (abs($n) >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 2, ',', ''), '0'), ',') . ' jt';
        }

        if (abs($n) >= 1_000) {
            return rtrim(rtrim(number_format($n / 1_000, 1, ',', ''), '0'), ',') . ' rb';
        }

        return number_format($n, 0, ',', '.');
    }
}
