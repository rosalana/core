<?php

namespace Rosalana\Core\Services\Logging;

class BasecampSendScheme extends LogScheme
{
    public function format(): void
    {
        $record = $this->trace()->getDecision();
        $decision = $record['decision'];

        $entry = $this->entry(status: 'info');

        $entry->addActor('Service:Basecamp');
        $entry->setTimestamp($record['timestamp']);

        $entry->addFlag('target', $decision['target']);

        $entry->addMessage(strtoupper($decision['method']) . ' ' . $decision['endpoint'] . ':' . $decision['status']);
    }

    public function formatException(): void
    {
        $record = $this->trace()->getException();
        $exception = $record['exception'];

        $entry = $this->entry(status: 'error');
        $entry->setTimestamp($record['timestamp']);

        $entry->addActor('Service:Basecamp');

        $entry->addFlag('file', $exception->getFile());

        if ($record['data']['class'] ?? false) {
            $entry->addFlag('class', $record['data']['class']);
        }

        $entry->addMessage($exception->getMessage());
    }
}
