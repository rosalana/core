<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Support\Signer;

class TicketSigner extends Signer
{
    public function __construct(
        protected array $ticket,
        ?int $timestamp = null
    ) {
        $this->timestamp = $timestamp ?? $this->now();
    }

    protected function getData(): string
    {
        return "{$this->ticket['id']}\n{$this->timestamp}";
    }

    protected function getSecretKey(): string
    {
        return $this->ticket['key'];
    }
}
