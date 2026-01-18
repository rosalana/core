<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Str;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Basecamp;
use Rosalana\Core\Facades\Trace;
use Rosalana\Core\Traits\Serviceable;

class Manager
{
    use Serviceable;

    protected string $origin;

    protected array $targets = [];

    protected string|null $correlationId = null;

    protected array $excepts = [];

    protected string|null $name;

    public function __construct()
    {
        $this->origin = App::slug();
    }

    public function worker(): void
    {
        (new Worker($this->origin))();
    }

    public function to(string|array $apps): self
    {
        $this->targets = is_array($apps) ? $apps : [$apps];
        return $this;
    }

    public function except(string|array $apps): self
    {
        $this->excepts = is_array($apps) ? $apps : [$apps];
        return $this;
    }

    public function broadcast(): self
    {
        $this->targets = ['*'];
        return $this;
    }

    public function responseTo(Message $message): self
    {
        $this->correlationId = $message->correlationId;
        $this->targets = [$message->from];
        $this->name = $message->name();

        return $this;
    }

    public function request(?string $name = null, array $payload = []): Promise
    {
        if (!is_null($name)) {
            $this->validateName($name);
            $this->name = $name;
        }

        $message = Trace::capture(fn() => $this->send('request', $payload), 'Outpost:send');

        return new Promise(Message::make(
            id: (string) Str::uuid(),
            data: $message
        ));
    }

    public function confirm(?string $name = null, array $payload = []): Promise
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $message = Trace::capture(fn() => $this->send('confirmed', $payload), 'Outpost:send');

        return new Promise(Message::make(
            id: (string) Str::uuid(),
            data: $message
        ));
    }

    public function fail(?string $name = null, array $payload = []): Promise
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $message = Trace::capture(fn() => $this->send('failed', $payload), 'Outpost:send');

        return new Promise(Message::make(
            id: (string) Str::uuid(),
            data: $message
        ));
    }

    public function unreachable(?string $name = null, array $payload = []): Promise
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $message = Trace::capture(fn() => $this->send('unreachable', $payload), 'Outpost:send');

        return new Promise(Message::make(
            id: (string) Str::uuid(),
            data: $message
        ));
    }

    protected function send(string $status, array $payload = []): array
    {
        if (!empty($this->excepts)) {
            $this->targets = array_filter(
                $this->targets,
                fn($target) => !in_array($target, $this->excepts)
            );

            if (contains($this->targets, '*')) {
                array_push($this->targets, ...array_map(fn($app) => '!' . $app, $this->excepts));
            }
        }

        if (empty($this->targets)) {
            throw new \RuntimeException("No target apps specified for Outpost message.");
        }

        Trace::decision([
            'status' => $status,
            'name' => $this->name,
            'targets' => $this->targets,
            'correlation_id' => $this->correlationId,
        ]);

        $message = Message::make(id: (string) Str::uuid(), data: [
            'from' => $this->origin,
            'to' => $this->targets,
            'correlation_id' => $this->correlationId,
            'payload' => $payload,
            'namespace' => $this->name . ':' . $status,
        ]);

        $response = Basecamp::post("/outpost/send", [
            'from' => $this->origin,
            'to' => $this->targets,
            'correlation_id' => $this->correlationId,
            'payload' => $payload,
            'namespace' => $this->name . ':' . $status,
        ]);

        $message->correlationId = $response->json('data.message.correlation_id');

        App::hooks()->run('outpost:send', $message);

        $this->reset();

        return $response->json('data.message');
    }

    public function receive(string $namespace, \Closure $callback, string $name = 'unknown'): void
    {
        Registry::register($namespace, $callback, $name);
    }

    public function receiveSilently(string $namespace, \Closure $callback, string $name = 'unknown'): void
    {
        Registry::registerSilent($namespace, $callback, $name);
    }

    public function runRegistry(Message $message): bool
    {
        return Registry::run($message);
    }

    public function reset(): void
    {
        $this->targets = [];
        $this->correlationId = null;
        $this->excepts = [];
        $this->name = null;
    }

    protected function validateName(string $name): void
    {
        $fail = false;

        if (empty($name)) $fail = true;
        if (str_contains($name, ' ')) $fail = true;
        if (count(explode('.', $name)) !== 2) $fail = true;
        if (strtolower($name) != $name) $fail = true;

        if ($fail) {
            throw new \InvalidArgumentException("Invalid Outpost message name: {$name}");
        }
    }
}
