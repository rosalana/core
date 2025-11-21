<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Facades\Pipeline;
use Rosalana\Core\Services\Basecamp\RequestStrategies\AppStrategy;
use Rosalana\Core\Services\Basecamp\RequestStrategies\BasecampStrategy;
use Rosalana\Core\Traits\Serviceable;

class Manager
{
    use Serviceable;

    protected Request $request;

    protected ?string $pipeline = null;

    public function __construct()
    {
        $this->reset();
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
        $response = $this->request->method($method)->send($endpoint, $data);

        if ($this->pipeline) {
            Pipeline::resolve($this->pipeline)->run($response);
        }

        $this->reset();

        return $response;
    }

    public function withPipeline(string $pipeline): self
    {
        $this->pipeline = $pipeline;

        return $this;
    }

    public function withAuth(?string $token = null): self
    {
        if (empty($token)) {
            $token = \Rosalana\Core\Session\TokenSession::get();
        }

        $this->request->authorization('Bearer', $token);
        return $this;
    }

    public function to(string $name): self
    {
        $this->request->strategy(new AppStrategy($name));
        return $this;
    }

    public function version(string $version): self
    {
        $this->request->version($version);
        return $this;
    }

    /**
     * Add a callback to be executed after the request is completed.
     */
    public function after(string $alias, \Closure $callback)
    {
        return Pipeline::extend($alias, function ($response, $next) use ($callback) {
            $callback($response);
            return $next($response);
        });
    }

    protected function reset(): void
    {
        $this->request = new Request();
        $this->request->strategy(new BasecampStrategy());
        $this->pipeline = null;
    }
}
