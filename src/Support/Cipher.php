<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Facades\App;

class Cipher
{
    public static function encrypt(string $value): string
    {
        $data = base64_decode($value);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');

        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength);

        $key = substr(hash('sha256', self::getSecretKey(), true), 0, 32);

        return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function decrypt(string $value): string
    {
        $key = substr(hash('sha256', self::getSecretKey(), true), 0, 32);
        $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));

        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public static function wrapToString(array $data): string
    {
        return base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    public static function unwrapFromString(string $data): array
    {
        $decoded = base64_decode($data);
        return json_decode($decoded, true) ?? [];
    }

    protected static function getSecretKey(): string
    {
        $secret = App::config('basecamp.secret');

        if (!$secret) {
            throw new \RuntimeException('Cannot create signature without secret token.');
        }

        return $secret;
    }
}
