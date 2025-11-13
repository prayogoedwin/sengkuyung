<?php

namespace App\Helpers;

class FileEncryption
{
    private static $salt = 'KualaLumpur2025';
    private static $cipher = 'AES-256-CBC';

    /**
     * Encrypt file content
     */
    public static function encryptFile($fileContent)
    {
        // Generate key dari salt
        $key = hash('sha256', self::$salt, true);
        
        // Generate random IV
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        // Encrypt content
        $encrypted = openssl_encrypt(
            $fileContent,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Gabungkan IV dengan encrypted data
        // IV diperlukan untuk decrypt, jadi disimpan di awal file
        return $iv . $encrypted;
    }

    /**
     * Decrypt file content
     */
    public static function decryptFile($encryptedContent)
    {
        // Generate key dari salt yang sama
        $key = hash('sha256', self::$salt, true);
        
        // Extract IV dari awal file
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($encryptedContent, 0, $ivLength);
        
        // Extract encrypted data
        $encrypted = substr($encryptedContent, $ivLength);
        
        // Decrypt
        return openssl_decrypt(
            $encrypted,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}