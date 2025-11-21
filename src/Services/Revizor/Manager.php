<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Services\Basecamp\Request;
use Rosalana\Core\Support\Cipher;

class Manager
{
    public function ticket(array|string|null $ticket = null): Ticket|TicketManager
    {
        if (is_null($ticket)) {
            return new TicketManager();
        }

        return Ticket::from($ticket);
    }

    public function ticketFor(string $target): Ticket
    {
        $tickets = $this->ticket();

        if ($tickets->inWallet($target)) {
            return $tickets->find($target);
        }

        return $tickets->buy($target);
    }

    public function verifyRequest(): Ticket
    {
        return Ticket::fromRequest()->verify();
    }

    public function verify(string|array|Ticket $ticket): Ticket
    {
        return (is_string($ticket) || is_array($ticket) ? Ticket::from($ticket) : $ticket)->verify();
    }

    public function request(Request $request): RequestManager
    {
        return new RequestManager(
            method: $request->getMethod(),
            url: $request->getUrl(),
            body: $request->getBody()
        );
    }

    public function hide($value)
    {
        return Cipher::encrypt($value);
    }

    public function reveal($value)
    {
        return Cipher::decrypt($value);
    }

    public function encrypt($value)
    {
        return $this->hide($value);
    }

    public function decrypt($value)
    {
        return $this->reveal($value);
    }
}
