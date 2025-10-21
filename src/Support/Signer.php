<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Facades\App;

abstract class Signer
{
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

        return hash_hmac('sha256', $data, $secret);
    }

    public function compare(string $signature): bool
    {
        return hash_equals($signature, $this->sign());
    }

    protected function now(): int
    {
        return (int) (microtime(true) * 1000);
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
