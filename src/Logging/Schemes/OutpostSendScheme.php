<?php

namespace Rosalana\Core\Logging\Schemes;

use Rosalana\Core\Services\Logging\LogScheme;

class OutpostSendScheme extends LogScheme
{
    public function format(): void
    {
        $record = $this->trace()->getDecision();
        $decisiton = $record['decision'];

        $entry = $this->entry(status: 'info');

        $entry->addActor('Service:Outpost');
        $entry->setTimestamp($record['timestamp']);

        foreach ($decisiton['targets'] as $target) {
            if ($target === '*') {
                $entry->addFlag('target', 'broadcast');
            }

            if (str_starts_with($target, '!')) {
                $entry->addFlag('target', 'except:' . substr($target, 1));
            } else {
                $entry->addFlag('target', $target);
            }
        }

        $entry->addMessage($decisiton['name'] . ':' . $decisiton['status']);
    }

    public function formatException(): void
    {
        $record = $this->trace()->getException();
        $exception = $record['exception'];

        $entry = $this->entry(status: 'error');
        $entry->setTimestamp($record['timestamp']);

        $entry->addActor('Service:Outpost');

        $entry->addFlag('file', $exception->getFile());

        if ($record['data']['class'] ?? false) {
            $entry->addFlag('class', $record['data']['class']);
        }

        $entry->addMessage($exception->getMessage());
    }
}
