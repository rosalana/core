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
use Rosalana\Core\Facades\Revizor;

class TicketValidator
{
    protected int $SIGNATURE_TTL = 60;
    protected string $CACHE_KEY_PREFIX = 'revizor_signatures_';

    protected ?Ticket $comparing_ticket = null;

    public function __construct(
        protected Ticket $ticket,
    ) {
        $this->SIGNATURE_TTL = App::config('revizor.signature_ttl', 60);
        $this->CACHE_KEY_PREFIX = App::config('revizor.cache_prefix', 'revizor_signatures_');
        $this->checkFormat();
    }

    public function determineState(): string
    {
        $p = $this->ticket->getPayload();

        if (($p['locked'] ?? false) === true && isset($p['key'])) return 'locked';
        if (($p['locked'] ?? false) === false && isset($p['key'])) return 'unlocked';
        if (!isset($p['key']) && isset($p['signature'], $p['timestamp'])) return 'signed';

        return 'unknown';
    }

    public function checkFormat()
    {
        $state = $this->determineState();

        switch ($state) {
            case 'locked':
                $this->locked();
                break;
            case 'unlocked':
                $this->unlocked();
                break;
            case 'signed':
                $this->signed();
                break;
            default:
                throw new InvalidTicketFormatException('Unknown ticket state');
        }
    }

    protected function shared()
    {
        if (
            !$this->ticket->has('id') ||
            !$this->ticket->has('expires_at') ||
            !$this->ticket->has('target') ||
            !$this->ticket->has('audience')
        ) {
            throw new InvalidTicketFormatException();
        }
    }

    protected function locked()
    {
        $this->shared();

        if (!$this->ticket->has('key')) {
            throw new InvalidTicketFormatException('Locked ticket format is invalid.');
        }

        if ($this->ticket->has('signature') || $this->ticket->has('timestamp')) {
            throw new InvalidTicketFormatException('Locked ticket format is invalid.');
        }
    }

    protected function unlocked()
    {
        $this->shared();

        if (!$this->ticket->has('key')) {
            throw new InvalidTicketFormatException('Unlocked ticket format is invalid.');
        }

        if ($this->ticket->has('signature') || $this->ticket->has('timestamp')) {
            throw new InvalidTicketFormatException('Unlocked ticket format is invalid.');
        }
    }

    protected function signed()
    {
        $this->shared();

        if ($this->ticket->has('key')) {
            throw new InvalidTicketFormatException('Signed ticket format is invalid.');
        }

        if (!$this->ticket->has('signature') || !$this->ticket->has('timestamp')) {
            throw new InvalidTicketFormatException('Signed ticket format is invalid.');
        }
    }

    public function verify(): bool
    {
        if (request()->attributes->get('_revizor_validated') === true) {
            return true;
        }

        if (!$this->ticket->isSigned()) {
            throw new InvalidTicketFormatException('Only signed tickets can be verified.');
        }

        $this->checkExpiration();
        $this->checkTimestamp();
        $this->checkReplay();
        $this->checkTicketExists();
        $this->checkSignature();

        request()->attributes->set('_revizor_validated', true);

        return true;
    }

    protected function checkExpiration()
    {
        if ($this->ticket->isExpired()) {
            throw new TicketExpiredException('Ticket has expired.');
        }
    }

    protected function checkTimestamp()
    {
        $now = (int) (microtime(true) * 1000);

        if (abs($now - $this->ticket->payload(('timestamp'))) > $this->SIGNATURE_TTL * 1000) {
            throw new SignatureExpiredException();
        }
    }

    protected function checkReplay()
    {
        $signature = $this->ticket->payload(('signature'));
        $ticketId = $this->ticket->payload('id');
        $cacheKey = $this->CACHE_KEY_PREFIX . $ticketId;

        $signatures = Cache::get($cacheKey, []);

        if (in_array($signature, $signatures, true)) {
            throw new ReplayedSignatureException();
        }

        $signatures[] = $signature;
        if (count($signatures) > 1000) {
            array_shift($signatures);
        }

        Cache::put($cacheKey, $signatures, now()->addSeconds($this->SIGNATURE_TTL));
    }

    protected function checkTicketExists()
    {
        $ticket = Revizor::ticket()->search($this->ticket->payload(('id')));

        if (!$ticket) {
            throw new TicketNotFoundException();
        }

        $this->comparing_ticket = $ticket;
    }

    protected function checkSignature()
    {
        TicketSigner::make(ticket: $this->comparing_ticket, timestamp: $this->ticket->payload(('timestamp')))->compare($this->ticket->payload(('signature'))) || throw new SignatureMismatchException();

        $this->comparing_ticket = null;
    }
}
