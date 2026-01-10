<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Facades\Trace;

abstract class Listener
{
    public function handle(Message $message): void
    {
        Trace::capture(function () use ($message) {
            $status = $message->status();

            $job = match ($status) {
                'request' => $this->request($message),
                'confirmed' => $this->confirmed($message),
                'failed' => $this->failed($message),
                'unreachable' => $this->unreachable($message),
                default => $this->unreachable($message),
            };

            if (!$job) return null;

            if ($job instanceof Action) {
                $result = run($job);

                Trace::decisionWhen(!!$result, [
                    'handler' => 'Resolved `' . static::class . '`',
                    'queued' => $result->isQueueable(),
                    'broadcasted' => $result->isBroadcastable(),
                ]);
            } else {
                event($job);
            }
        }, 'Outpost:handler:listener');
    }

    abstract public function request(Message $message);

    public function confirmed(Message $message)
    {
        return $message->event(function (Message $message) {
            //
        });
    }

    public function failed(Message $message)
    {
        return $message->event(function (Message $message) {
            // throw... or log...
        });
    }

    public function unreachable(Message $message)
    {
        return $message->event(function (Message $message) {
            // throw... or log...
        });
    }
}
