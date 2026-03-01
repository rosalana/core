<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Events\BasecampRequestSent;
use Rosalana\Core\Facades\Trace;
use Rosalana\Core\Services\Basecamp\RequestStrategies\AppStrategy;
use Rosalana\Core\Services\Basecamp\RequestStrategies\BasecampStrategy;
use Rosalana\Core\Traits\Serviceable;

class Manager
{
    use Serviceable;

    protected Request $request;

    protected bool $mocked = false;

    protected bool $ghost = false;

    protected \Closure|null $fallback = null;

    protected \Closure|null $onSuccess = null;

    protected \Closure|null $onFail = null;

    public function __construct()
    {
        $this->request = new Request();
        $this->request->strategy(new BasecampStrategy());
    }

    /**
     * General method for making GET requests.
     */
    public function get(string $endpoint, array $data = [])
    {
        return Trace::capture(fn() => $this->request('get', $endpoint, $data), 'Basecamp:send');
    }

    /**
     * General method for making POST requests.
     */
    public function post(string $endpoint, array $data = [])
    {
        return Trace::capture(fn() => $this->request('post', $endpoint, $data), 'Basecamp:send');
    }

    /**
     * General method for making PUT requests.
     */
    public function put(string $endpoint, array $data = [])
    {
        return Trace::capture(fn() => $this->request('put', $endpoint, $data), 'Basecamp:send');
    }

    /**
     * General method for making PATCH requests.
     */
    public function patch(string $endpoint, array $data = [])
    {
        return Trace::capture(fn() => $this->request('patch', $endpoint, $data), 'Basecamp:send');
    }

    /**
     * General method for making DELETE requests.
     */
    public function delete(string $endpoint, array $data = [])
    {
        return Trace::capture(fn() => $this->request('delete', $endpoint, $data), 'Basecamp:send');
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
                    try {
                        $fallbackResult = ($this->fallback)($e);

                        return $fallbackResult instanceof Response
                            ? $fallbackResult
                            : $this->fakeResponse($e);
                    } catch (\Throwable) {
                        // fallback failed, fall through to onFail
                    }
                }

                if ($this->onFail) {
                    ($this->onFail)($e);

                    return $this->fakeResponse($e);
                }

                throw $e;
            }
        }

        if (! $this->mocked) {
            Trace::decision([
                'method' => $request->getMethod(),
                'endpoint' => $request->getUrl(),
                'status' => $response->status(),
                'target' => $request->getTarget(),
                'pipeline' => '', // remove later
            ]);

            event(new BasecampRequestSent($request, $response));
        }

        if ($this->onSuccess && ! $this->ghost) {
            ($this->onSuccess)($response);
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
     * Ghost the request (skip onSuccess callback).
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

    /**
     * Set a fallback to recover from a failed request.
     */
    public function fallback(\Closure $callback): self
    {
        $this->fallback = $callback;
        return $this;
    }

    /**
     * Register a callback to be called on successful response.
     */
    public function onSuccess(\Closure $callback): self
    {
        $this->onSuccess = $callback;
        return $this;
    }

    /**
     * Register a callback to be called when the request fails (after fallback).
     */
    public function onFail(\Closure $callback): self
    {
        $this->onFail = $callback;
        return $this;
    }

    /**
     * Generate a fake response for fallback or onFail when an exception occurs.
     */
    protected function fakeResponse(\Exception $e): Response
    {
        return $this->request->fake([
            'status' => 'fake',
            'data' => [
                'message' => 'This is a fallback response due to an exception: ' . $e->getMessage(),
            ],
        ]);
    }
}
