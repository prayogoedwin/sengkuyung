<?php

namespace App\Helpers;

class Helper
{
    public static function encodeId($id)
    {
        return base64_encode($id);
    }

    public static function decodeId($encodedId)
    {
        return base64_decode($encodedId);
    }
}
