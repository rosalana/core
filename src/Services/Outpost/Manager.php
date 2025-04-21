<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Event;
use Rosalana\Core\Facades\Basecamp;

class Manager
{
    /**
     * Connection name for Rosalana Outpost.
     */
    protected string $connection;

    /**
     * Queue name for Rosalana Outpost.
     */
    protected string $queue;

    /**
     * Origin for the Packet.
     */
    protected string $origin;

    /**
     * Target for the Packet.
     */
    protected string|null $target = null;

    /**
     * Services that the client can use.
     */
    protected array $services = [];

    public function __construct()
    {
        $this->connection = config('rosalana.outpost.connection');
        $this->queue = config('rosalana.outpost.queue');
        $this->origin = config('rosalana.basecamp.name');
    }

    /**
     * Set the target for the Packet.
     */
    public function to(string $name): self
    {
        $this->target = $name;
        return $this;
    }

    /**
     * Send a packet to the target application or all applications.
     */
    public function send(string $alias, array $payload = []): void
    {
        $packet = new Packet(
            alias: $alias,
            origin: $this->origin,
            target: $this->target,
            queue: $this->queue,
            payload: $payload,
        );

        if ($this->target === $this->origin) {
            throw new \InvalidArgumentException("[Outpost] Cannot send a packet to the same app: {$this->origin}");
        }

        if (empty($this->target)) {
            $response = Basecamp::apps()->all();
            $apps = collect($response->json('data'))
                ->filter(fn($app) => $app['self'] !== true);

            foreach ($apps as $app) {
                dispatch($packet)
                    ->onConnection($this->connection)
                    ->onQueue($this->queue . '.' . $app['name']);
            }
        } else {
            dispatch($packet)->onConnection($this->connection)->onQueue($this->queue . '.' . $this->target);
        }

        $this->reset();
    }

    /**
     * Register a listener for a specific Outpost event.
     * This automatically includes the correct prefix based on configuration.
     */
    public function receive(string|array $alias, string|\Closure|array|null $listener = null): void
    {
        if (is_array($alias)) {
            foreach ($alias as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subListener) {
                        Event::listen("{$this->queue}.{$key}", $subListener);
                    }
                } else {
                    Event::listen("{$this->queue}.{$key}", $value);
                }
            }
            return;
        }
    
        if (is_array($listener)) {
            foreach ($listener as $subListener) {
                Event::listen("{$this->queue}.{$alias}", $subListener);
            }
            return;
        }
    
        Event::listen("{$this->queue}.{$alias}", $listener);
    }

    /**
     * Reset instance to default values.
     */
    public function reset(): self
    {
        $this->target = null;
        return $this;
    }

    /**
     * Register a new sub-service.
     */
    public function registerService(string $name, $instance): void
    {
        $this->services[$name] = $instance;
    }

    /**
     * Invoke a sub-service.
     */
    public function __call($method, $arg)
    {
        if (isset($this->services[$method])) {
            $service = $this->services[$method];

            if (method_exists($service, 'setManagerContext')) {
                $service->setManagerContext($this);
            }
            return $service;
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist on BasecampManager.");
    }
}
