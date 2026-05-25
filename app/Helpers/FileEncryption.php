<?php

namespace App\Helpers;

use RuntimeException;

/**
 * AES-256-CBC file encryption.
 *
 * Format ciphertext yang dihasilkan: [IV 16 byte][ciphertext PKCS7-padded].
 * Method streaming menghasilkan byte yang IDENTIK dengan in-memory encrypt
 * sehingga file lama tetap bisa di-decrypt (dan sebaliknya).
 */
class FileEncryption
{
    private static $salt = 'KualaLumpur2025';
    private static $cipher = 'AES-256-CBC';

    private const BLOCK_SIZE = 16;
    private const CHUNK_SIZE = 65536; // 64 KB, kelipatan BLOCK_SIZE

    /**
     * Encrypt string penuh (in-memory). Tetap dipertahankan untuk backward compat.
     * Untuk file besar gunakan {@see self::encryptToStream()}.
     */
    public static function encryptFile($fileContent)
    {
        $key = self::deriveKey();
        $iv = openssl_random_pseudo_bytes(self::BLOCK_SIZE);

        $encrypted = openssl_encrypt(
            $fileContent,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $iv . $encrypted;
    }

    /**
     * Decrypt string penuh (in-memory). Tetap dipertahankan untuk backward compat.
     * Untuk file besar gunakan {@see self::decryptToStream()}.
     */
    public static function decryptFile($encryptedContent)
    {
        $key = self::deriveKey();
        $iv = substr($encryptedContent, 0, self::BLOCK_SIZE);
        $encrypted = substr($encryptedContent, self::BLOCK_SIZE);

        return openssl_decrypt(
            $encrypted,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Enkripsi streaming: baca dari resource $in, tulis ciphertext ke resource $out.
     * Memory yang digunakan ~CHUNK_SIZE (default 64 KB) berapapun ukuran file.
     *
     * @param  resource  $in   read-mode resource (mis. fopen($path, 'rb'))
     * @param  resource  $out  write-mode resource (mis. fopen($dest, 'wb'))
     */
    public static function encryptToStream($in, $out): void
    {
        self::assertResource($in, 'input');
        self::assertResource($out, 'output');

        $iv = openssl_random_pseudo_bytes(self::BLOCK_SIZE);
        fwrite($out, $iv);

        self::transform($in, $out, $iv, true);
    }

    /**
     * Dekripsi streaming: baca encrypted dari resource $in, tulis plaintext ke resource $out.
     *
     * @param  resource  $in
     * @param  resource  $out
     */
    public static function decryptToStream($in, $out): void
    {
        self::assertResource($in, 'input');
        self::assertResource($out, 'output');

        $iv = fread($in, self::BLOCK_SIZE);
        if ($iv === false || strlen($iv) !== self::BLOCK_SIZE) {
            throw new RuntimeException('File terenkripsi rusak: IV tidak lengkap.');
        }

        self::transform($in, $out, $iv, false);
    }

    private static function transform($in, $out, string $iv, bool $encrypting): void
    {
        $key = self::deriveKey();
        $currentIv = $iv;
        $buffer = '';

        while (true) {
            $chunk = fread($in, self::CHUNK_SIZE);
            if ($chunk === false) {
                throw new RuntimeException('Gagal membaca stream sumber.');
            }
            if ($chunk !== '') {
                $buffer .= $chunk;
            }

            $endOfInput = ($chunk === '') || feof($in);

            if ($endOfInput) {
                break;
            }

            // Sisakan minimal BLOCK_SIZE byte di buffer agar blok terakhir
            // diproses dengan PKCS7 padding setelah loop berakhir.
            if (strlen($buffer) <= self::BLOCK_SIZE) {
                continue;
            }

            $processableLen = intdiv(strlen($buffer) - self::BLOCK_SIZE, self::BLOCK_SIZE) * self::BLOCK_SIZE;
            if ($processableLen <= 0) {
                continue;
            }

            $block = substr($buffer, 0, $processableLen);
            $buffer = substr($buffer, $processableLen);

            $result = $encrypting
                ? openssl_encrypt($block, self::$cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $currentIv)
                : openssl_decrypt($block, self::$cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $currentIv);

            if ($result === false) {
                throw new RuntimeException($encrypting ? 'Gagal mengenkripsi blok.' : 'Gagal mendekripsi blok.');
            }

            fwrite($out, $result);

            // CBC chaining: IV blok berikutnya = 16 byte terakhir ciphertext.
            // Saat encrypt, ciphertext = $result. Saat decrypt, ciphertext = $block (input).
            $cipherSource = $encrypting ? $result : $block;
            $currentIv = substr($cipherSource, -self::BLOCK_SIZE);
        }

        // Blok terakhir dengan PKCS7 padding (encrypt) / un-padding (decrypt).
        $final = $encrypting
            ? openssl_encrypt($buffer, self::$cipher, $key, OPENSSL_RAW_DATA, $currentIv)
            : openssl_decrypt($buffer, self::$cipher, $key, OPENSSL_RAW_DATA, $currentIv);

        if ($final === false) {
            throw new RuntimeException($encrypting ? 'Gagal mengenkripsi blok akhir.' : 'Gagal mendekripsi blok akhir.');
        }

        fwrite($out, $final);
    }

    private static function deriveKey(): string
    {
        return hash('sha256', self::$salt, true);
    }

    private static function assertResource($handle, string $label): void
    {
        if (!is_resource($handle)) {
            throw new RuntimeException("Argumen \"{$label}\" harus berupa resource (hasil fopen).");
        }
    }
}
