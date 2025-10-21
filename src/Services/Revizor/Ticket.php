<?php

namespace Rosalana\Core\Services\Revizor;

use Rosalana\Core\Support\Cipher;

class Ticket
{
    /** Original ticket data */
    protected array $original;
    
    /** Current ticket data */
    protected array $payload;

    public static function make(array|string $ticket = []): self
    {
        $instance = new self();

        if (is_string($ticket)) {
            $instance->original = Cipher::unwrapFromString($ticket);
            $instance->payload = $instance->original;
        } else {
            $instance->original = $ticket;
            $instance->payload = $ticket;
        }

        return $instance;
    }

    public function payload(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }

}