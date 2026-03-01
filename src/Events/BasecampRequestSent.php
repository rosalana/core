<?php

namespace Rosalana\Core\Events;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Services\Basecamp\Request;

class BasecampRequestSent
{
    public function __construct(
        public readonly Request $request,
        public readonly Response $response,
    ) {}
}
