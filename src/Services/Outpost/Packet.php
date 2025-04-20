<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class Packet implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue;

    public function __construct(
        public string $alias, // e.g. 'notification.email'
        public string $origin,
        public ?string $target,
        public array $payload,
    ) {}

    public function handle()
    {
        $callback = Registry::resolve($this->alias);

        if ($callback) {
            call_user_func($callback, $this->payload);
        }
    }
}
