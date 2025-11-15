<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Services\Revizor\RequestSigner as Signer;

class RequestManager
{
    protected array $headers = [];

    protected ?Signer $signer;

    public function __construct(
        protected string $method,
        protected string $url,
        protected mixed $body = [],
    ) {}

    protected function signer(?int $timestamp = null): Signer
    {
        $this->signer = new Signer(
            method: $this->method,
            url: $this->url,
            body: $this->body,
            timestamp: $timestamp,
        );

        return $this->signer;
    }

    public function sign(?int $timestamp = null): self
    {
        $this->signer($timestamp)->sign();

        return $this;
    }

    public function headers(): array
    {
        if (!$this->signer) {
            $this->sign();
        }

        return [
            'X-App-Id' => $this->signer->getId(),
            'X-Timestamp' => $this->signer->getTimestamp(),
            'X-Signature' => $this->signer->getSignature(),
        ];
    }
}
