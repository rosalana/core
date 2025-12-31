<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Redis;
use Rosalana\Core\Actions\Outpost\MessageReceived;
use Rosalana\Core\Facades\App;

class Worker
{
    protected string $connection;
    protected string $stream;
    protected string $origin;

    public function __construct(string $origin)
    {
        $this->connection = App::config('outpost.connection', 'default');
        $this->stream = 'outpost:' . App::slug();
        $this->origin = $origin;
    }

    public function __invoke()
    {
        $this->ensureConsumerGroup();

        while (true) {
            $messages = Redis::connection($this->connection)->xreadgroup(
                App::slug(),
                gethostname() . '-' . getmypid(),
                [$this->stream => '>'],
                1,
                5000
            );

            if (empty($messages)) {
                continue;
            }

            foreach ($messages[$this->stream] ?? [] as $id => $payload) {

                $ok = true;
                $startTime = microtime(true);

                try {
                    run(new MessageReceived($id, $payload));
                } catch (\Throwable $e) {
                    $ok = false;
                }

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $symbol = $ok ? '✓' : '✗';

                echo ('[' . date('Y-m-d H:i:s') . '] ');
                echo ("{$symbol} " . ($payload['namespace'] ?? $id) . ' ' . ($payload['from'] ? '(' . $payload['from'] . ')' : '') . ' ............. ~ ' . $executionTime . 'ms' . PHP_EOL);

                Redis::connection($this->connection)->xack(
                    $this->stream,
                    App::slug(),
                    [$id]
                );
            }
        }
    }

    protected function ensureConsumerGroup(): void
    {
        try {
            Redis::connection($this->connection)->xgroup(
                'CREATE',
                $this->stream,
                App::slug(),
                '0',
                true
            );
        } catch (\Throwable $e) {
            // Group probably already exists
        }
    }
}
