<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Facades\App;

abstract class Signer
{
    private const SIGNATURE_PATTERN = '/^([a-f0-9]{64})\.([a-f0-9]{16})$/';

    protected int $timestamp;
    protected string $signature;

    abstract protected function getData(): string;

    public static function make(mixed ...$arg): self
    {
        return new static(...$arg);
    }

    public function sign(?string $nonce = null): string
    {
        $nonce = $nonce ?? bin2hex(random_bytes(8));

        $data = $this->getData() . "\n" . $nonce;
        $secret = $this->getSecretKey();

        if (!$secret) {
            throw new \RuntimeException('Cannot create signature without secret token.');
        }

        $this->signature = hash_hmac('sha256', $data, $secret) . '.' . $nonce;

        return $this->signature;
    }

    public function compare(string $signature): bool
    {
        if (!preg_match(self::SIGNATURE_PATTERN, $signature, $matches)) {
            return false;
        }

        return hash_equals($signature, $this->sign($matches[2]));
    }

    protected function now(): int
    {
        $now = (int) (microtime(true) * 1000);
        $this->timestamp = $now;
        return $now;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    protected function getSecretKey(): string
    {
        $secret = App::config('basecamp.secret');

        if (!$secret) {
            throw new \RuntimeException('Cannot create signature without secret token.');
        }

        return $secret;
    }
}
