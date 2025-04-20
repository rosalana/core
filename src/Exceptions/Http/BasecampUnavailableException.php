<?php

namespace Rosalana\Core\Exceptions\Http;

class BasecampUnavailableException extends HttpException
{

    public function __construct(array $response = [])
    {
        parent::__construct($response, 'Basecamp is currently unavailable. Please try again later.', 503);
    }

    public function getType(): string
    {
        return 'UNAVAILABLE';
    }

    public function getErrors(): array
    {
        return [];
    }
}
