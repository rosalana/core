<?php

namespace Rosalana\Core\Exceptions\Service\Outpost;

use Rosalana\Core\Services\Outpost\Message;

class InvalidMessageNamespaceException extends OutpostException
{
    public function __construct(?Message $outpostMessage = null, string $message = 'The namespace must follow the format "group.action:status" with allowed status.')
    {
        return parent::__construct($outpostMessage, $message);
    }
}
