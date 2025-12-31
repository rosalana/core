<?php

namespace Rosalana\Core\Exceptions\Service\Outpost;

use Rosalana\Core\Services\Outpost\Message;

class OutpostException extends \Exception
{
    public function __construct(protected ?Message $outpostMessage = null, string $message = "An error occurred while Outpost process.")
    {
        return parent::__construct($message);
    }

    public function getOutpostMessage(): ?Message
    {
        return $this->outpostMessage;
    }
}