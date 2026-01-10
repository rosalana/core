<?php

namespace Rosalana\Core\Logging\Schemes;

use Rosalana\Core\Services\Logging\LogScheme;
use Rosalana\Core\Services\Trace\Trace;

class OutpostHandlerScheme extends LogScheme
{
    public function format(): void
    {
        $name = $this->getHandlerName($this->trace());

        if ($name === 'registry') {
            foreach ($this->trace()->phases() as $phase) {
                $silent = $phase->findRecords(fn($r) => $r['data']['silent'] ?? false)[0]['data']['silent'] ?? false;

                $entry = $this->entry(status: 'info');
                $entry->addActor($name);
                $entry->setTimestamp($phase->startTime());

                $entry->addFlag('silent', $silent);
                $entry->addFlag('queue', false);
                $entry->addFlag('broadcast', false);

                if ($decision = $phase->getRecordByType('decision')) {
                    $entry->addFlag('queue', $decision['data']['queued'] ?? false);
                    $entry->addFlag('broadcast', $decision['data']['broadcast'] ?? false);
                    $entry->addMessage($decision['data']['handler'] . $decision['timestamp']);
                }

                if ($exception = $phase->getRecordByType('exception')) {
                    $entry->addStatus('error');
                    $entry->addMessage($exception['exception']->getMessage(). $exception['timestamp']);
                }
            }
        } else {
            $record = $this->trace()->getDecision();
            $silent = $this->trace()->findRecords(fn($r) => $r['data']['silent'] ?? false)[0]['data']['silent'] ?? false;

            $entry = $this->entry(status: 'info');
            $entry->addActor($name);

            $entry->addFlag('silent', $silent);
            $entry->addFlag('queue', $record['data']['queued'] ?? false);
            $entry->addFlag('broadcast', $record['data']['broadcast'] ?? false);

            $entry->addMessage($record['data']['handler'] . $record['timestamp']);
        }
    }

    private function getHandlerName(Trace $trace): string
    {
        return explode('Outpost:handler:', $trace->name())[1] ?? 'unknown';
    }
}
