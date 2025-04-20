<?php

namespace Rosalana\Core\Session;

use Carbon\Carbon;

class TokenSession
{
    public static function set(string $token, ?string $expiresAt = null): void
    {
        session()->put('rosalana.token', $token);

        if ($expiresAt) {
            session()->put('rosalana.token_expires_at', $expiresAt);
        }
    }

    public static function get(): ?string
    {
        return session()->get('rosalana.token');
    }

    public static function forget(): void
    {
        session()->forget('rosalana.token');
        session()->forget('rosalana.token_expires_at');
    }

    public static function expiresAt(): ?Carbon
    {
        $expiresAt = session()->get('rosalana.token_expires_at');

        if ($expiresAt) {
            try {
                return Carbon::parse($expiresAt);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
