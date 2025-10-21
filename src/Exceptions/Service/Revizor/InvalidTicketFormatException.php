<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class InvalidTicketFormatException extends \Exception
{
    public function __construct($message = "Invalid ticket format.")
    {
        parent::__construct($message);
    }
}