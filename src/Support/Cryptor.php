<?php

namespace Rosalana\Core\Support;

class Cryptor
{
    public static function sign(string $method, string $url, array $body = [], ?int $timestamp = null): array
    {
        $secret = config('rosalana.basecamp.secret');
        $id = config('rosalana.basecamp.id');

        if (!$secret) {
            throw new \RuntimeException('Cannot sign request without app secret.');
        }

        if (!$timestamp) {
            $timestamp = time();
        }

        $method = strtoupper($method);
        $bodyString = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES);
        $data = "{$method}\n{$url}\n{$timestamp}\n{$bodyString}\n{$id}";

        $computed = hash_hmac('sha256', $data, $secret);

        return [
            'X-App-Id' => $id,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $computed,
        ];
    }


    public static function unsecret(string $value): string
    {
        $token = env('ROSALANA_APP_SECRET');

        if (!$token) {
            throw new \RuntimeException('Cannot decrypt without app token.');
        }

        $data = base64_decode($value);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');

        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength);

        $key = substr(hash('sha256', $token, true), 0, 32);

        return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function secret(string $value): string
    {
        $token = env('ROSALANA_APP_SECRET');

        if (!$token) {
            return '****';
        }

        $key = substr(hash('sha256', $token, true), 0, 32);
        $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));

        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }
}
