<?php

namespace Rosalana\Core\Contracts\Basecamp;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Services\Basecamp\Request;

interface RequestStrategy
{
    public function getTarget(): string;

    public function prepare(Request $request): Request;

    public function throw(\Exception|string $e, Response $response): void;
}
