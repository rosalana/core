<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Rosalana\Core\Facades\Basecamp;
use Rosalana\Core\Traits\Serviceable;

class Manager
{
    use Serviceable;

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
     * Packet targets
     */
    protected array|null $targets = null;

    /**
     * Packet receivers
     */
    protected array|null $receivers = null;

    /**
     * Excluded receivers|targets
     */
    protected array $excepts = [];

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
     * Send a packet to the target application or all applications specified.
     * 
     * @param string $alias The event name/alias for the packet
     * @param array $payload The data to be sent with the packet
     * @return void
     */
    public function send(string $alias, array $payload = []): void
    {
        $globalId = config('rosalana.account.identifier', 'rosalana_account_id');
        $userId = Auth::user()?->$globalId ?? null;

        $packet = new Packet(
            alias: $alias,
            origin: $this->origin,
            userId: $userId,
            queue: $this->queue,
            payload: $payload,
        );

        $targets = $this->targets ?? $this->resolveTargetApps();

        foreach ($targets as $target) {
            if ($target === $this->origin) continue;

            $packet->target = $target;
            dispatch(clone $packet)
                ->onConnection($this->connection)
                ->onQueue("{$this->queue}.{$target}");
        }

        $this->reset();
    }

    /**
     * Register a listener for a specific Outpost event.
     * This automatically includes the correct prefix based on configuration.
     */
    public function receive(string $alias, string|\Closure $listener): void
    {
        Event::listen("{$this->queue}.{$alias}", function (Packet $packet) use ($listener) {
            if (!empty($this->receivers) && !in_array($packet->origin, $this->receivers)) {
                return;
            }

            if (in_array($packet->origin, $this->excepts)) {
                return;
            }

            if ($packet->origin === $this->origin) {
                return;
            }

            is_string($listener) ? app($listener)->handle($packet) : $listener($packet);
        });

        $this->reset();
    }

    protected function resolveTargetApps(): array
    {
        $response = Basecamp::apps()->all();

        return collect($response->json('data'))
            ->filter(fn($app) => $app['self'] !== true && !in_array($app['name'], $this->excepts))
            ->pluck('name')
            ->toArray();
    }

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
}
