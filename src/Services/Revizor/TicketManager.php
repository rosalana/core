<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Basecamp;

class TicketManager
{
    /**
     * Find ticket for given target in my tickets
     */
    public function find(string $target): ?Ticket
    {
        $ticket = App::context()->get('ticket.' . $target);

        if ($ticket) {
            return Ticket::from($ticket);
        }

        return null;
    }

    /**
     * Check if ticket exists on Basecamp
     */
    public function search(string|Ticket $ticketOrId): ?Ticket
    {
        $list = App::context()->get('tickets');

        if (!$list) {
            $list = Basecamp::tickets()->list()->json('data.tickets');
            App::context()->put('tickets', $list, 86400);
        }

        foreach ($list as $ticket) {
            if ($ticket['id'] === (is_string($ticketOrId) ? $ticketOrId : $ticketOrId->payload('id'))) {
                return Ticket::from($ticket);
            }
        }

        return null;
    }

    /**
     * Buy ticket from Basecamp to given target
     */
    public function buy(string $target): Ticket
    {
        $ticket = Ticket::from(
            Basecamp::ticket()->create(['target' => $target])->json('data.ticket')
        );

        App::context()->put('ticket.' . $target, $ticket->toString(), $ticket->getTTL());

        return $ticket;
    }

    public function inWallet(string $target): bool
    {
        return App::context()->has('ticket.' . $target);
    }
}
