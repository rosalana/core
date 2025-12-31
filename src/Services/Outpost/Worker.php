<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Redis;
use Rosalana\Core\Actions\Outpost\MessageReceived;
use Rosalana\Core\Exceptions\Service\Outpost\OutpostException;
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

                $message = null;
                $queued = false;
                $broadcasted = false;
                $via = 'none';

                try {
                    $action = run(new MessageReceived($id, $payload));
                    $message = $action->getMessage();
                    $queued = $action->executedToQueue;
                    $broadcasted = $action->executedToBroadcast;
                    $via = $action->executedVia;
                } catch (OutpostException $e) {
                    $message = $e->getOutpostMessage();
                    $ok = false;
                } catch (\Throwable $e) {
                    $ok = false;
                }

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $this->log($message, $via, $queued, $broadcasted, $executionTime, $ok);

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

    protected function log(?Message $message, string $provider, bool $queued, bool $broadcasted, float $time, bool $ok = true): void
    {
        $timestamp = $ok ? date('Y-m-d H:i:s') : $this->color(date('Y-m-d H:i:s'), 'red');

        $symbol = match (true) {
            !$ok => $this->color('✗', 'red'),
            $provider === 'none' => $this->color('!', 'yellow'),
            default => $this->color('✓', 'green'),
        };

        $namespace = '-';
        $from = '-';
        $via = '-';
        $flags = [];

        if ($message instanceof Message) {

            $namespace = $this->styleNamespace($message);
            $from = $message->from;

            $via = $this->styleVia($provider);

            if ($queued ?? false) {
                $flags[] = $this->color('queue', 'yellow');
            }

            if ($broadcasted ?? false) {
                $flags[] = $this->color('broadcast', 'red');
            }
        }

        $flags = $flags ? ' → ' . implode(',', $flags) : '';

        switch ($time) {
            case $time >= 50:
                $time = $this->color(number_format($time, 2) . 'ms', 'red');
                break;
            case $time >= 10:
                $time = $this->color(number_format($time, 2) . 'ms', 'yellow');
                break;
            default:
                $time = number_format($time, 2) . "ms";
        }

        echo sprintf(
            "[%s] %s %s (%s) [%s%s] ........ ~ %s\n",
            $timestamp,
            $symbol,
            $namespace,
            $from,
            $via,
            $flags,
            $time
        );
    }

    protected function styleNamespace(Message $message): string
    {
        $result = $message->name();

        return match ($message->status()) {
            'request' => $result . $this->color(':request', 'blue'),
            'confirmed' => $result . $this->color(':confirmed', 'green'),
            'failed'    => $result . $this->color(':failed', 'red'),
            'unreachable' => $result . $this->color(':unreachable', 'yellow'),
            default   => $result . ":{$message->status()}" ?? ':unknown',
        };
    }

    protected function styleVia(string $provider): string
    {
        if (str_starts_with($provider, 'failed:')) {
            return $this->color('failed:', 'red') . $this->styleVia(str_replace('failed:', '', $provider));
        }

        return match ($provider ?? '-') {
            'promise' => $this->color('promise', 'cyan'),
            'listener' => $this->color('listener', 'green'),
            'registry' => $this->color('registry', 'blue'),
            default => $this->color($provider ?? '-', 'gray'),
        };
    }

    protected function color(string $text, string $color): string
    {
        $c = fn(string $code) => "\033[{$code}m";
        $reset = $c('0');

        $gray   = $c('90');
        $green  = $c('32');
        $red    = $c('31');
        $blue   = $c('34');
        $cyan   = $c('36');
        $yellow = $c('33');

        return match ($color) {
            'gray'   => "{$gray}{$text}{$reset}",
            'green'  => "{$green}{$text}{$reset}",
            'red'    => "{$red}{$text}{$reset}",
            'blue'   => "{$blue}{$text}{$reset}",
            'cyan'   => "{$cyan}{$text}{$reset}",
            'yellow' => "{$yellow}{$text}{$reset}",
            default  => $text,
        };
    }
}
