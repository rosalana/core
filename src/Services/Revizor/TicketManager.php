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
        $ticket = App::context()->get("tickets.{$target}", null);

        if ($ticket) {
            return Ticket::from($ticket);
        }

        return null;
    }

    /**
     * Check if ticket exists on Basecamp
     */
    public function search(int|Ticket $ticketOrId): ?Ticket
    {
        $list = App::context()->get('well-known.tickets', []);

        if (empty($list) || !is_array($list)) {
            $list = Basecamp::tickets()->list()->json('data.tickets');
            App::context()->put('well-known.tickets', $list, 86400);
        }

        foreach ($list as $ticket) {
            if ($ticket['id'] === (is_int((int)$ticketOrId) ? $ticketOrId : $ticketOrId->payload('id'))) {
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
            Basecamp::tickets()->create(['target' => $target])->json('data.ticket')
        );

        App::context()->put("tickets.{$target}", $ticket->toString(), $ticket->getTTL());

        return $ticket;
    }

    public function inWallet(string $target): bool
    {
        return App::context()->has("tickets.{$target}");
    }
}
