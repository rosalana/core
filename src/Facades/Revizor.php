<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana Revizor service.
 * 
 * @method static \Rosalana\Core\Services\Revizor\Ticket|\Rosalana\Core\Services\Revizor\TicketManager ticket(string|array|null $ticket = null)
 * @method static \Rosalana\Core\Services\Revizor\Ticket ticketFor(string $target)
 * @method static \Rosalana\Core\Services\Revizor\Ticket verifyRequest()
 * @method static \Rosalana\Core\Services\Revizor\Ticket verify(string|array|\Rosalana\Core\Services\Revizor\Ticket $ticket)
 * @method static \Rosalana\Core\Services\Revizor\RequestManager request(\Rosalana\Core\Services\Basecamp\Request $request)
 * @method static string hide(mixed $value)
 * @method static mixed reveal(mixed $value)
 * @method static string encrypt(mixed $value)
 * @method static mixed decrypt(mixed $value)
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