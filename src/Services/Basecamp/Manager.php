<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Support\Facades\Http;

class Manager
{
    /**
     * Base URL of the Rosalana Basecamp.
     */
    protected string $url;

    /**
     * Secret app key of the client.
     */
    protected string $secret;

    /**
     * Origin of the client app.
     */
    protected string $origin;

    /**
     * Headers for the HTTP requests.
     */
    protected array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * Services that the client can use.
     */
    protected array $services = [];

    public function __construct()
    {
        $this->url = config('rosalana.auth.basecamp_url');
        $this->secret = config('rosalana.auth.app_secret');
        $this->origin = config('rosalana.auth.app_origin');

        $this->headers['X-App-Secret'] = $this->secret;
        $this->headers['Origin'] = $this->origin;
    }

    /**
     * Add auth token to the headers.
     */
    public function withAuth(string $token): self
    {
        $new = clone $this;
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $new;
    }

    /**
     * General method for making GET requests.
     */
    public function get(string $endpoint)
    {
        return Http::withHeaders($this->headers)->get($this->url . $endpoint);
    }

    /**
     * General method for making POST requests.
     */
    public function post(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->headers)->post($this->url . $endpoint, $data);
    }

    /**
     * General method for making PUT requests.
     */
    public function put(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->headers)->put($this->url . $endpoint, $data);
    }

    /**
     * General method for making PATCH requests.
     */
    public function patch(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->headers)->patch($this->url . $endpoint, $data);
    }

    /**
     * General method for making DELETE requests.
     */
    public function delete(string $endpoint)
    {
        return Http::withHeaders($this->headers)->delete($this->url . $endpoint);
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
