<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Rosalana\Core\Exceptions\BasecampErrorType;
use Rosalana\Core\Exceptions\HttpAppErrorType;
use Rosalana\Core\Facades\Basecamp;
use Rosalana\Core\Facades\Pipeline;

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
     * Version of the API to be used.
     */
    protected string $version;

    /**
     * Headers for the HTTP requests.
     */
    protected array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * Pipeline to be used for the request.
     */
    protected ?string $pipeline = null;

    /**
     * Services that the client can use.
     */
    protected array $services = [];

    public function __construct()
    {
        $this->url = config('rosalana.basecamp.url');
        $this->secret = config('rosalana.basecamp.secret');
        $this->version = "/api/" . config('rosalana.basecamp.version') . "/";

        $this->headers['X-App-Secret'] = $this->secret;
        $this->headers['X-App-Proxy'] = false;
        $this->headers['Origin'] = config('app.url');
    }

    /**
     * Set the version of the API to be used.
     */
    public function version(string $version): self
    {
        $this->version = "/api/" . $version . "/";
        return $this;
    }

    /**
     * Redirect the request to a specific app.
     * @throws \Rosalana\Core\Exceptions\AppUnreachableException
     * @throws \Rosalana\Core\Exceptions\AppUnavailableException
     * @throws \Rosalana\Core\Exceptions\BasecampUnavailableException
     */
    public function to(string $name): self
    {
        try {
            $response = Basecamp::apps()->find($name);
        } catch (\Rosalana\Core\Exceptions\Http\BasecampUnavailableException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Rosalana\Core\Exceptions\Http\AppUnreachableException();
        }

        if (empty($response->json('url'))) {
            throw new \Rosalana\Core\Exceptions\Http\AppUnreachableException(
                'The app is unreachable. The URL is unknown in the system.'
            );
        }

        $this->url = $response->json('url');

        $this->headers['X-App-Proxy'] = true;
        return $this;
    }

    /**
     * Add auth token to the headers.
     */
    public function withAuth(?string $token = null): self
    {
        if (empty($token)) {
            $token = \Rosalana\Core\Session\TokenSession::get();
        }

        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    /**
     * Set the pipeline to be used for the request.
     */
    public function withPipeline(string $pipeline): self
    {
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * General method for making GET requests.
     */
    public function get(string $endpoint, ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('get', $endpoint);
    }

    /**
     * General method for making POST requests.
     */
    public function post(string $endpoint, array $data = [], ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('post', $endpoint, $data);
    }

    /**
     * General method for making PUT requests.
     */
    public function put(string $endpoint, array $data = [], ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('put', $endpoint, $data);
    }

    /**
     * General method for making PATCH requests.
     */
    public function patch(string $endpoint, array $data = [], ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('patch', $endpoint, $data);
    }

    /**
     * General method for making DELETE requests.
     */
    public function delete(string $endpoint, ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('delete', $endpoint);
    }

    /**
     * General method for making requests.
     */
    protected function request(string $method, string $endpoint, array $data = []): Response
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->$method($this->url . $this->version . $endpoint, $data);
        } catch (\Exception $e) {
            if (!empty($this->headers['X-App-Proxy'])) {
                throw new \Rosalana\Core\Exceptions\Http\AppUnavailableException();
            } else {
                throw new \Rosalana\Core\Exceptions\Http\BasecampUnavailableException();
            }
        }

        if ($response->json('status') !== 'ok') {

            if (!empty($this->headers['X-App-Proxy'])) {
                $type = HttpAppErrorType::tryFrom($response->json('type') ?? 'UNKNOWN') ?? HttpAppErrorType::UNKNOWN;
            } else {
                $type = BasecampErrorType::tryFrom($response->json('type') ?? 'UNKNOWN') ?? BasecampErrorType::UNKNOWN;
            }

            $type->throw($response->json());
        }

        if ($this->pipeline) {
            Pipeline::resolve($this->pipeline)->run($response);
            $this->pipeline = null;
        }

        return $response;
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
