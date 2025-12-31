<?php

namespace Rosalana\Core\Actions\Outpost;

use Rosalana\Core\Exceptions\Service\Outpost\OutpostException;
use Rosalana\Core\Services\Actions\Inline as InlineAction;
use Rosalana\Core\Services\Outpost\Message;

class Inline extends InlineAction
{
    public function __construct(\Closure $handler, protected Message $message)
    {
        parent::__construct($handler);

        $this->broadcastOn = [str_replace('.', '-', $this->message->name())];
        $this->broadcastAs = $this->message->name() . '.' . $this->message->status();
    }

    public function handle(): void
    {
        try {
            ($this->handler())($this->message);
        } catch (\Throwable $e) {
            $this->message->fail(['error' => $e->getMessage()]);
            throw new OutpostException($this->message, "Inline action handling failed: " . $e->getMessage());
        }
    }
}
