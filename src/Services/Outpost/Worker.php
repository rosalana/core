<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Redis;
use Rosalana\Core\Actions\Outpost\MessageReceived;
use Rosalana\Core\Exceptions\Service\Outpost\OutpostException;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Trace;

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

                Trace::start('Outpost:receive');

                try {
                    run(new MessageReceived($id, $payload));
                } catch (\Throwable $e) {
                    // dont crash the worker
                }

                $trace = Trace::finish();
                Trace::flush();

                $trace->log('console');


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
