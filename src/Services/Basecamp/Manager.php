<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Rosalana\Core\Facades\Pipeline;
use Rosalana\Core\Services\Basecamp\RequestStrategies\AppStrategy;
use Rosalana\Core\Services\Basecamp\RequestStrategies\BasecampStrategy;
use Rosalana\Core\Traits\Serviceable;

class Manager
{
    use Serviceable;

    protected Request $request;

    protected ?string $pipeline = null;

    protected bool $mocked = false;

    protected bool $ghost = false;

    protected \Closure|null $fallback = null;

    public function __construct()
    {
        $this->request = new Request();
        $this->request->strategy(new BasecampStrategy());
    }

    /**
     * General method for making GET requests.
     */
    public function get(string $endpoint, array $data = [], ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('get', $endpoint, $data);
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
    public function delete(string $endpoint, array $data = [], ?string $pipeline = null)
    {
        if ($pipeline) {
            $this->pipeline = $pipeline;
        }

        return $this->request('delete', $endpoint, $data);
    }

    /**
     * General method for making requests.
     */
    protected function request(string $method, string $endpoint, array $data = []): Response
    {
        $request = $this->request->method($method);
        if ($this->mocked) {
            $response = $request->mock($endpoint, $data);
        } else {
            try {
                $response = $request->send($endpoint, $data);
            } catch (\Exception $e) {
                if ($this->fallback) {
                    $callback = $this->fallback;
                    $fallbackResponse = $callback($e);
                    if ($fallbackResponse instanceof Response) {
                        return $fallbackResponse;
                    } else {
                        return $this->request->fake([
                            'status' => 'ok',
                            'data' => [
                                'message' => 'This is a fallback response due to an exception: ' . $e->getMessage(),
                            ],
                        ]);
                    }
                } else {
                    throw $e;
                }
            }
        }

        if ($this->pipeline && !$this->ghost) {
            Pipeline::resolve($this->pipeline)->run($response);
        }

        return $response;
    }

    /**
     * Mock the request.
     */
    public function mock(): self
    {
        $this->mocked = true;
        return $this;
    }

    /**
     * Ghost the request (skip pipelines).
     */
    public function ghost(): self
    {
        $this->ghost = true;
        return $this;
    }

    /**
     * Set the request timeout.
     */
    public function timeout(int $seconds): self
    {
        $this->request->timeout($seconds);
        return $this;
    }

    /**
     * Set the request retry attempts.
     */
    public function retry(int $times): self
    {
        $this->request->retry($times);
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
     * Add auth token to the headers.
     */
    public function withAuth(?string $token = null): self
    {
        if (empty($token)) {
            $token = \Rosalana\Core\Session\TokenSession::get();
        }

        $this->request->authorization('Bearer', $token);
        return $this;
    }

    /**
     * Redirect the request to a specific app.
     */
    public function to(string $name): self
    {
        $this->request->strategy(new AppStrategy($name));
        return $this;
    }

    /**
     * Set the version of the API to be used.
     */
    public function version(string $version): self
    {
        $this->request->version($version);
        return $this;
    }

    public function fallback(\Closure $callback): self
    {
        $this->fallback = $callback;
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
}
