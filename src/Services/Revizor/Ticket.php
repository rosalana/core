<?php

namespace Rosalana\Core\Services\Revizor;

use Illuminate\Support\Carbon;
use Rosalana\Core\Services\Revizor\TicketSigner;
use Rosalana\Core\Support\Cipher;
use Rosalana\Core\Services\Revizor\TicketValidator as Validator;

class Ticket
{
    protected Validator $validator;

    public function __construct(
        protected array $payload = [],
    ) {
        $this->validator = new Validator($this);

        if (!empty($this->payload['expires_at'])) {
            $this->payload['expires_at'] = Carbon::parse($this->payload['expires_at']);
        }
    }

    public static function from(string|array $payload = []): self
    {
        if (is_string($payload)) {
            $payload = Cipher::unwrap($payload);
        }

        return new self($payload);
    }

    public function isSigned(): bool
    {
        return $this->validator->determineState() === 'signed';
    }

    public function isLocked(): bool
    {
        return $this->validator->determineState() === 'locked';
    }

    public function isUnlocked(): bool
    {
        return $this->validator->determineState() === 'unlocked';
    }

    public function isExpired(): bool
    {
        $expires = $this->payload('expires_at');

        if (is_null($expires)) {
            return false;
        }

        return $expires->isPast();
    }

    public function lock(): self
    {
        if (!$this->isUnlocked()) return $this;

        $this->payload['key'] = Cipher::encrypt($this->payload['key']);
        $this->payload['locked'] = true;

        return $this;
    }

    public function unlock(): self
    {
        if (!$this->isLocked()) return $this;

        $this->payload['key'] = Cipher::decrypt($this->payload['key']);
        $this->payload['locked'] = false;

        return $this;
    }

    public function sign(?int $timestamp = null): self
    {
        if (!$this->isUnlocked()) {
            $this->unlock();
        }

        $signer = TicketSigner::make($this, $timestamp);
        $this->payload['signature'] = $signer->sign();
        $this->payload['timestamp'] = $signer->getTimestamp();
        unset($this->payload['key'], $this->payload['locked']);

        return $this;
    }

    public function verify(): self
    {
        $this->validator->verify();

        return $this;
    }

    public function payload(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->payload[$key]);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTTL(): ?int
    {
        $expires = $this->payload('expires_at');

        if (is_null($expires)) {
            return null;
        }

        if ($expires->isPast()) {
            return 0;
        }

        return now()->diffInSeconds($expires);
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function toString(): string
    {
        return Cipher::wrap($this->payload);
    }

    public function seal(): string
    {
        return $this->toString();
    }
}
