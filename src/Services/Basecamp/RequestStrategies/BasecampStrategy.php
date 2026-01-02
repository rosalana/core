<?php

namespace Rosalana\Core\Services\Basecamp\RequestStrategies;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Contracts\Basecamp\RequestStrategy;
use Rosalana\Core\Enums\BasecampErrorType;
use Rosalana\Core\Exceptions\Http\BasecampUnavailableException;
use Rosalana\Core\Facades\Revizor;
use Rosalana\Core\Services\Basecamp\Request;

class BasecampStrategy implements RequestStrategy
{
    public function getTarget(): string
    {
        return 'basecamp';
    }

    public function prepare(Request $request): Request
    {
        $request->url(config('rosalana.basecamp.url'));
        $request->version(config('rosalana.basecamp.version'));
        $request->prefix('/api/');

        $request->headers(
            Revizor::request($request)
                ->sign()
                ->headers()
        );

        return $request;
    }

    public function throw(\Exception|string $e, Response $response): void
    {
        if (is_string($e)) {
            BasecampErrorType::tryFrom($e)?->throw($response->json());
        } else {
            throw new BasecampUnavailableException();
        }
    }
}
