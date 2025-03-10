<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Crypt;

class Helper
{
    // public static function encodeId($id)
    // {
    //     return base64_encode($id);
    // }

    // public static function decodeId($encodedId)
    // {
    //     return base64_decode($encodedId);
    // }

    private static $salt = 'P3Z0Q'; // Ganti dengan salt yang aman
    public static function encodeId($id)
    {
        $idWithSalt = $id . self::$salt; // Tambahkan salt
        return base64_encode($idWithSalt);
    }

    public static function decodeId($encodedId)
    {
        $decoded = base64_decode($encodedId);
        return str_replace(self::$salt, '', $decoded); // Hapus salt
    }

}
