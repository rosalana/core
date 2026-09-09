<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Facades\App;

class Cipher
{

    /**
     * Encrypt the given value by secret_key to make it safe.
     */
    public static function encrypt(string $value): string
    {
        $key = substr(hash('sha256', self::getSecretKey(), true), 0, 32);
        $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Cannot encrypt value.');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt the given value by secret_key to retrieve original data.
     */
    public static function decrypt(string $value): string
    {
        $data = base64_decode($value, true);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');

        if ($data === false || strlen($data) <= $ivLength) {
            throw new \InvalidArgumentException('Invalid encrypted value.');
        }

        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength);
        $key = substr(hash('sha256', self::getSecretKey(), true), 0, 32);
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Cannot decrypt value.');
        }

        return $decrypted;
    }

    /**
     * Wrap data into a string for transport.
     */
    public static function wrap(array $data): string
    {
        return base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Unwrap data from a transported string to array.
     */
    public static function unwrap(string $data): array
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
