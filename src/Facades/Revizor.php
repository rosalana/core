<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana Revizor service.
 * 
 * @method static \Rosalana\Core\Services\Revizor\Ticket|\Rosalana\Core\Services\Revizor\TicketManager ticket(string|array|null $ticket = null)
 * @method static \Rosalana\Core\Services\Revizor\Ticket ticketFor(string $target)
 * 
 * @see \Rosalana\Core\Services\Revizor\Manager
 */
class Revizor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.revizor';
    }
}