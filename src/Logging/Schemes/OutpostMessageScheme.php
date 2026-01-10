<?php

namespace Rosalana\Core\Logging\Schemes;

use Rosalana\Core\Services\Logging\LogScheme;

class OutpostMessageScheme extends LogScheme
{
    public function format(): void
    {
        $record = $this->trace()->getDecision();
        /** @var \Rosalana\Core\Services\Outpost\Message $message */
        $message = $record['data']['message'];

        $this->entry(status: 'info')
            ->addActor('message')
            ->setTimestamp($record['timestamp'])
            ->addFlag('from', $message->from)
            ->addFlag('correlation_id', $message->correlationId)
            ->addFlag('id', $message->id)
            ->addMessage($message->namespace);
    }
}
