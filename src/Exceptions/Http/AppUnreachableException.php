<?php

namespace Rosalana\Core\Exceptions\Http;

class AppUnreachableException extends RosalanaHttpException {

    public function __construct(array $response = [])
    {
        parent::__construct($response, 'The app is unreachable. Please check your connection and try again.', 503);
    }

    public function getType(): string
    {
        return 'APP_UNREACHABLE';
    }

    public function getErrors(): array
    {
        return [];
    }
}