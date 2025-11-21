<?php

namespace Rosalana\Core\Traits;

trait Serviceable
{
    protected array $services = [];

    /**
     * Register a new sub-service.
     */
    public function registerService(string $name, $instance): void
    {
        $this->services[$name] = $instance;
    }

    /**
     * Check if a sub-service is registered.
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Invoke a sub-service dynamically.
     */
    public function __call($method, $arguments)
    {
        if (isset($this->services[$method])) {
            $service = $this->services[$method];

            if (method_exists($service, 'setManagerContext')) {
                $service->setManagerContext($this);
            }

            return $service;
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist.");
    }
}
