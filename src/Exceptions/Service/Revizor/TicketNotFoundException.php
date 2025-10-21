<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class TicketNotFoundException extends \Exception
{
    public function __construct($message = "Unknown ticket.")
    {
        parent::__construct($message);
    }
}