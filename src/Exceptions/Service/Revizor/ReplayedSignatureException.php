<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class ReplayedSignatureException extends \Exception
{
    public function __construct($message = "Replay attempt detected.")
    {
        parent::__construct($message);
    }
}