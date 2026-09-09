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
        $id = $ticketOrId instanceof Ticket ? $ticketOrId->payload('id') : $ticketOrId;
        $list = App::context()->get('well-known.tickets', []);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($attempt === 1) {
                $list = Basecamp::tickets()->list()->json('data.tickets');
                App::context()->put('well-known.tickets', $list, 86400);
            }

            foreach (is_array($list) ? $list : [] as $ticket) {
                if ($ticket['id'] === $id) {
                    $candidate = Ticket::from($ticket);

                    if (! $candidate->isExpired()) {
                        return $candidate;
                    }
                }
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
