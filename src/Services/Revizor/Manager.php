<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Support\Cipher;

class Manager
{
    public function __construct(
        protected TicketManager $ticketManager
    ) {}

    public function ticket(array|string|null $ticket = null): Ticket|TicketManager
    {
        return $ticket === null
            ? $this->ticketManager
            : Ticket::make($ticket);
    }
    /**
     * TicketManager je pro Find ticket nebo create a vrací Ticket
     * Ticket je samotný Ticket
     * TicketManager může provádět i validace
     */

    public function request()
    {
        // sign request TODO
    }

    public function hide($value)
    {
        return Cipher::encrypt($value);
    }

    public function reveal($value)
    {
        return Cipher::decrypt($value);
    }
}
