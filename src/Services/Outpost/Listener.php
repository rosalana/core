<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Contracts\Action;

abstract class Listener
{
    public function handle(Message $message): Action|null
    {
        $status = $message->status();

        $job = match ($status) {
            'request' => $this->request($message),
            'confirmed' => $this->confirmed($message),
            'failed' => $this->failed($message),
            'unreachable' => $this->unreachable($message),
            default => $this->unreachable($message),
        };

        if ($job instanceof Action) {
            run($job);
            return $job;
        } else {
            event($job);
        }

        return null;
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
