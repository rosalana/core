<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Facades\App;

abstract class Signer
{
    protected int $timestamp;
    protected string $signature;

    abstract protected function getData(): string;

    public static function make(...$arg): self
    {
        return new static(...$arg);
    }

    public function sign()
    {
        $data = $this->getData();
        $secret = $this->getSecretKey();

        if (!$secret) {
            throw new \RuntimeException('Cannot create signature without secret token.');
        }

        $this->signature = hash_hmac('sha256', $data, $secret);

        return $this->signature;
    }

    public function compare(string $signature): bool
    {
        return hash_equals($signature, $this->sign());
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
