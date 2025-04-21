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
     * Send targets for packet
     * Send to one multiple or all
     */
    protected array|null $targets = null;

    /**
     * Receive from the target application or all applications.
     */
    protected array|null $receivers = null;

    /**
     * Exclude targets for packet when receiving from all
     */
    protected array $excepts = [];

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
     * Specifies targets for the packet to be sent to.
     * @param string|array|null $apps The app name(s) that should receive the packet
     * @return self
     */
    public function to(string|array|null $apps): self
    {
        $this->targets = is_null($apps) ? null : (array) $apps;
        return $this;
    }
    /**
     * Specifies from which apps the packet should be received.
     *
     * @param string|array|null $apps The app name(s) that should send the packet
     * @return self
     */
    public function from(string|array|null $apps): self
    {
        $this->receivers = is_null($apps) ? null : (array) $apps;
        return $this;
    }

    /**
     * Specifies which apps should be excluded from process
     *
     * @param string|array|null $apps The app name(s) that should be excluded
     * @return self
     */
    public function except(string|array|null $apps): self
    {
        $this->excepts = is_null($apps) ? [] : (array) $apps;
        return $this;
    }























    /**
     * Send a packet to the target application or all applications.
     */
    // public function send(string $alias, array $payload = []): void
    // {
    //     (new Sender())->send($alias, $payload);

    //     $this->reset();
    // }

    /**
     * Register a listener for a specific Outpost event.
     * This automatically includes the correct prefix based on configuration.
     */
    // public function receive(string $alias, string|\Closure $listener): void
    // {
    //     (new Receiver())->receive($alias, $listener);
    // }

    /**
     * Reset instance to default values.
     */
    public function reset(): self
    {
        $this->targets = null;
        $this->receivers = null;
        $this->excepts = [];
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
