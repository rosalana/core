<?php

namespace Rosalana\Core\Services\Basecamp;

use Rosalana\Core\Enums\HttpMethod;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Rosalana\Core\Contracts\Basecamp\RequestStrategy;
use Rosalana\Core\Exceptions\Http\RosalanaHttpException;

class Request
{
    protected string $url;

    protected string $prefix = '/api/';

    protected string $version = '';

    protected string $endpoint;

    protected array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    protected HttpMethod $method;

    protected int $timeout = 30;

    protected int $retry = 0;

    protected array $body = [];

    protected ?RequestStrategy $strategy = null;

    public function __construct()
    {
        $this->headers['Orgin'] = config('app.url');
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    public function getUrl(): string
    {
        return $this->serializeUrl();
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function strategy(RequestStrategy $strategy): self
    {
        $this->strategy = $strategy;
        return $this;
    }

    public function authorization(string $type, string $token): self
    {
        $this->headers['Authorization'] = "{$type} {$token}";
        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function version(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function method(string $method): self
    {
        $this->method = HttpMethod::from($method);
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function retry(int $times): self
    {
        $this->retry = $times;
        return $this;
    }

    public function send(string $endpoint, array $body = []): Response
    {
        $this->endpoint = $endpoint;
        $this->body = $body;

        if (!is_null($this->strategy)) {
            $this->strategy->prepare($this);
        }

        $url = $this->serializeUrl();

        try {
            $http = Http::withHeaders($this->headers)
                ->timeout($this->timeout);

            if ($this->retry > 0) {
                $http = $http->retry($this->retry);
            }

            $response = $http->{$this->method->value}($url, $this->body ?? []);
        } catch (\Exception $e) {
            if (!is_null($this->strategy)) {
                $this->strategy->throw(
                    $e,
                    new Response(new MockResponse([
                        'status' => 'error',
                        'type' => 'UNKNOWN',
                        'message' => $e->getMessage(),
                    ]))
                );
            } else {
                throw new RosalanaHttpException([], $e->getMessage(), $e->getCode(), $e);
            }
        }

        if ($response->json('status') !== 'ok') {
            if (!is_null($this->strategy)) {
                $this->strategy->throw($response->json('type') ?? 'UNKNOWN', $response);
            } else {
                throw new RosalanaHttpException($response->json(), 'HTTP request ended with error', $response->status());
            }
        }

        return $response;
    }

    public function mock(string $endpoint, array $body = []): Response
    {
        $this->endpoint = $endpoint;
        $this->body = $body;

        if (!is_null($this->strategy)) {
            $this->strategy->prepare($this);
        }

        return new Response(new MockResponse([
            'url' => $this->serializeUrl(),
            'method' => $this->method->value,
            'headers' => $this->headers,
            'body' => $this->body,
            'strategy' => $this->strategy ? get_class($this->strategy) : null,
        ]));
    }

    protected function serializeUrl(): string
    {
        if (empty($this->url) || empty($this->endpoint)) {
            throw new \RuntimeException("Cannot serialize URL: URL, version, or endpoint is missing.");
        }

        return implode('/', array_filter([
            rtrim($this->url, '/'),
            trim($this->prefix, '/'),
            trim($this->version, '/'),
            ltrim($this->endpoint, '/')
        ], fn($part) => !empty($part)));
    }
}
