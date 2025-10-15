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
            $timestamp = (int) (microtime(true) * 1000);
        }

        $method = strtoupper($method);
        $body = self::normalizeBody($body);
        $data = "{$method}\n{$url}\n{$timestamp}\n{$body}\n{$id}";

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

    protected static function normalizeBody(mixed $body): string
    {
        if ($body === null || $body === '' || $body === [] || $body === '{}' || $body === '[]') {
            return '';
        }

        if (is_string($body)) {
            $trimmed = trim($body);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    if ($decoded === [] || $decoded === null) {
                        return '';
                    }

                    return json_encode($decoded, JSON_UNESCAPED_SLASHES);
                }
            }

            return $trimmed;
        }

        if (is_array($body) || is_object($body)) {
            if (empty((array) $body)) {
                return '';
            }

            return json_encode($body, JSON_UNESCAPED_SLASHES);
        }

        return (string) $body;
    }
}
