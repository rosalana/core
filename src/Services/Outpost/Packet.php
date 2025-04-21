<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Rosalana\Core\Facades\Basecamp;

class Packet implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue;

    public function __construct(
        public string $alias,
        public string $origin,
        public string $queue,
        public ?string $target = null,
        public ?int $userId = null,
        public array $payload,
    ) {}

    public function handle()
    {
        event("{$this->queue}.{$this->alias}", $this);
    }

    public function user(): array|null
    {
        if (!Basecamp::hasService('users')) {
            return null;
        }

        return is_null($this->userId) ? null : Basecamp::users()->find($this->userId)->json('data');
    }
}
