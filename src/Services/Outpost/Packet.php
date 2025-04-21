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
        public string $alias, // e.g. 'notification.push'
        public string $origin,
        public ?string $target,
        public string $queue,
        public array $payload,
    ) {}

    public function handle()
    {
        event("{$this->queue}.{$this->alias}", $this);
    }
}
