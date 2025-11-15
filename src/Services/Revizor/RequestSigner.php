<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Support\Signer;

class RequestSigner extends Signer
{
    protected string $appId;

    public function __construct(
        protected string $method,
        protected string $url,
        protected mixed $body,
        ?int $timestamp = null,
    ) {
        $this->method = strtoupper($method);
        $this->appId = App::config('basecamp.id');
        $this->body = self::normalizeBody($body);
        $this->timestamp = $timestamp ?? $this->now();
    }

    protected function getData(): string
    {
        return "{$this->method}\n{$this->url}\n{$this->timestamp}\n{$this->body}\n{$this->appId}";
    }

    public function getId(): string
    {
        return $this->appId;
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
