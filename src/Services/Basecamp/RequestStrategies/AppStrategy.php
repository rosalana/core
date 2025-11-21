<?php

namespace Rosalana\Core\Services\Basecamp\RequestStrategies;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Contracts\Basecamp\RequestStrategy;
use Rosalana\Core\Enums\HttpAppErrorType;
use Rosalana\Core\Exceptions\Http\AppUnavailableException;
use Rosalana\Core\Facades\Basecamp;
use Rosalana\Core\Facades\Revizor;
use Rosalana\Core\Services\Basecamp\Request;

class AppStrategy implements RequestStrategy
{
    public function __construct(protected string $name) {}

    public function prepare(Request $request): Request
    {
        $request->url($this->findUrl());
        $request->version(config('rosalana.basecamp.version'));
        $request->prefix('/internal/');

        $request->authorization('Bearer', Revizor::ticketFor($this->name)->sign()->seal());

        return $request;
    }

    public function throw(\Exception|string $e, Response $response): void
    {
        if (is_string($e)) {
            HttpAppErrorType::tryFrom($e)?->throw($response->json());
        } else {
            throw new AppUnavailableException();
        }
    }

    protected function findUrl(): string
    {
        try {
            $response = Basecamp::apps()->find($this->name);
        } catch (\Rosalana\Core\Exceptions\Http\BasecampUnavailableException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Rosalana\Core\Exceptions\Http\AppUnreachableException(
                "The app '{$this->name}' is unreachable. Please check your connection and try again."
            );
        }

        if (empty($response->json('data.url'))) {
            throw new \Rosalana\Core\Exceptions\Http\AppUnreachableException(
                "The app '{$this->name}' is unreachable. The URL is unknown in the system."
            );
        }

        return $response->json('data.url');
    }
}
