<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class TicketExpiredException extends \Exception
{
    public function __construct($message = "Ticket has expired.")
    {
        parent::__construct($message);
    }
}
