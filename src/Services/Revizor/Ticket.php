<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Support\Cipher;

class Ticket
{
    /** Original ticket data */
    protected array $original;
    /** Current ticket data */
    protected array $payload;
    /** Key used for encryption/signing */
    protected string $key;

    protected $locked = true;

    public function __construct(array|string $ticket = [])
    {
        if (is_string($ticket)) {
            $this->original = Cipher::unwrapString($ticket);
            $this->payload = $this->original;
        } else {
            $this->original = $ticket;
            $this->payload = $ticket;
        }

        $this->key = App::config('revizor.key', 'key');
    }

    public static function make(array|string $ticket = []): self
    {
        return new self($ticket);
    }

    public function sign(?int $timestamp = null): self
    {
        $signer = TicketSigner::make(ticket: $this->payload, timestamp: $timestamp);
        $this->payload['signature'] = $signer->sign();
        $this->payload['timestamp'] = $signer->getTimestamp();
        unset($this->payload[$this->key]);

        return $this;
    }

    public function unlock(): self
    {
        // validation

        $this->payload[$this->key] = Cipher::decrypt($this->payload[$this->key]);
        $this->locked = false;

        return $this;
    }

    public function lock(): self
    {
        // validation

        $this->payload[$this->key] = Cipher::encrypt($this->payload[$this->key]);
        $this->locked = true;
        
        return $this;
    }

    public function verify(): bool
    {
        // TicketValidator...
        return true;
    }

    public function payload(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function seal()
    {
        return Cipher::wrapToString($this->payload);
    }
}
