<?php

namespace Rosalana\Core\Services\Revizor;

use Illuminate\Support\Facades\Cache;
use Rosalana\Core\Exceptions\Service\Revizor\InvalidTicketFormatException;
use Rosalana\Core\Exceptions\Service\Revizor\ReplayedSignatureException;
use Rosalana\Core\Exceptions\Service\Revizor\SignatureExpiredException;
use Rosalana\Core\Exceptions\Service\Revizor\SignatureMismatchException;
use Rosalana\Core\Exceptions\Service\Revizor\TicketExpiredException;
use Rosalana\Core\Exceptions\Service\Revizor\TicketNotFoundException;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Support\Cipher;

class TicketManager
{
    protected int $signature_ttl;
    protected string $cache_prefix;
    protected string $key;
    protected array $ticket;
    protected ?array $found_ticket = null;

    public function __construct(array|string $ticket = [])
    {
        $this->set($ticket);
        $this->signature_ttl = App::config('revizor.signature_ttl', 60);
        $this->cache_prefix = App::config('revizor.cache_prefix', 60);
        $this->key = App::config('revizor.key', 'key');
    }

    public function set(array|string $ticket): self
    {
        if (is_string($ticket)) {
            $this->ticket = $this->unwrap($ticket);
        } else {
            $this->ticket = $ticket;
        }
        return $this;
    }

    public function encrypt(): array
    {
        $this->checkUnsignedFormat();

        $this->ticket[$this->key] = Cipher::encrypt($this->ticket[$this->key]);

        return $this->ticket;
    }

    public function decrypt(): array
    {
        $this->checkUnsignedFormat();

        $this->ticket[$this->key] = Cipher::decrypt($this->ticket[$this->key]);

        return $this->ticket;
    }

    public function sign(?int $timestamp = null): array
    {
        $this->checkUnsignedFormat();

        $signer = TicketSigner::make(ticket: $this->ticket, timestamp: $timestamp);
        $this->ticket['signature'] = $signer->sign();
        $this->ticket['timestamp'] = $signer->getTimestamp();

        unset($this->ticket[$this->key]);

        return $this->ticket;
    }

    public function find(): ?array
    {
        // find this ticket on basecamp and encrypt it
        return [];
    }

    public function validate(): bool
    {
        if (request()->attributes->get('_revizor_validated') === true) {
            return true;
        }

        $this->checkSignedFormat(); // throws InvalidTicketFormatException
        $this->checkExpiration(); // throws TicketExpiredException
        $this->checkTimestamp(); // throws SignatureExpiredException || IncompleteSignatureException
        $this->checkReplay(); // throws ReplayedSignatureException
        $this->checkTicketExists(); // throws TicketNotFoundException
        $this->checkSignature(); // throws SignatureMismatchException

        request()->attributes->set('_revizor_validated', true);

        return true;
    }

    public function toString(): string
    {
        return $this->wrap($this->ticket);
    }

    protected function checkFormat(?array $ticket = null)
    {
        $ticket = $ticket ?? $this->ticket;

        if (is_string($ticket)) {
            throw new InvalidTicketFormatException();
        }

        if (!isset($ticket['id'])) {
            throw new InvalidTicketFormatException();
        }

        if (!isset($ticket['exp'])) {
            throw new InvalidTicketFormatException();
        }

        if (!isset($ticket['iss']) || !isset($ticket['aud'])) {
            throw new InvalidTicketFormatException();
        }
    }

    protected function checkUnsignedFormat(?array $ticket = null)
    {
        static::checkFormat($ticket);
        $ticket = $ticket ?? $this->ticket;

        if (!isset($ticket[$this->key])) {
            throw new InvalidTicketFormatException('Unsigned ticket format is invalid.');
        }

        if (isset($ticket['signature']) || isset($ticket['timestamp'])) {
            throw new InvalidTicketFormatException('Unsigned ticket format is invalid.');
        }
    }

    protected function checkSignedFormat(?array $ticket = null)
    {
        static::checkFormat($ticket);
        $ticket = $ticket ?? $this->ticket;

        if (isset($ticket[$this->key])) {
            throw new InvalidTicketFormatException('Unsigned ticket format is invalid.');
        }

        if (!isset($ticket['signature']) || !isset($ticket['timestamp'])) {
            throw new InvalidTicketFormatException('Unsigned ticket format is invalid.');
        }
    }

    protected function checkExpiration(?array $ticket = null)
    {
        $ticket = $ticket ?? $this->ticket;
        if ($ticket['exp'] < time()) {
            throw new TicketExpiredException('Ticket has expired.');
        }
    }

    protected function checkTimestamp()
    {
        $now = (int) (microtime(true) * 1000);

        if (abs($now - $this->ticket['timestamp']) > $this->signature_ttl * 1000) {
            throw new SignatureExpiredException();
        }
    }

    protected function checkReplay()
    {
        $signature = $this->ticket['signature'];
        $cacheKey = $this->cache_prefix . $this->ticket['id'];
        $signatures = Cache::get($cacheKey, []);

        if (in_array($signature, $signatures, true)) {
            throw new ReplayedSignatureException();
        }

        $signatures[] = $signature;
        if (count($signatures) > 1000) {
            array_shift($signatures);
        }

        Cache::put($cacheKey, $signatures, now()->addSeconds($this->signature_ttl));
    }

    protected function checkTicketExists()
    {
        $this->found_ticket = $this->find();

        if (!$this->found_ticket) {
            throw new TicketNotFoundException();
        }

        $this->checkUnsignedFormat($this->found_ticket);
        $this->checkExpiration($this->found_ticket);
    }

    protected function checkSignature()
    {
        TicketSigner::make(ticket: $this->found_ticket, timestamp: $this->ticket['timestamp'])->compare($this->ticket['signature']) || throw new SignatureMismatchException();
    }

    protected function wrap(array $unwrapped): string
    {
        return base64_encode(serialize($unwrapped));
    }

    protected function unwrap(string $wrapped): array
    {
        return unserialize(base64_decode($wrapped));
    }
}
