<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Basecamp;
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

    public function request(?string $name = null, array $payload = []): void
    {
        if (!is_null($name)) {
            $this->validateName($name);
            $this->name = $name;
        }

        $this->send('request', $payload);
    }

    public function confirm(?string $name = null, array $payload = []): void
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $this->send('confirmed', $payload);
    }

    public function fail(?string $name = null, array $payload = []): void
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $this->send('failed', $payload);
    }

    public function unreachable(?string $name = null, array $payload = []): void
    {
        if (!is_null($name)) {
            $this->validateName($name);
        }

        $this->send('unreachable', $payload);
    }

    protected function send(string $status, array $payload = []): void
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

        Basecamp::post("/outpost/send", [
            'from' => $this->origin,
            'to' => $this->targets,
            'correlation_id' => $this->correlationId,
            'payload' => $payload,
            'namespace' => $this->name . ':' . $status,
        ]);

        $this->reset();
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
