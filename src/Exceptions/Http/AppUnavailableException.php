<?php

namespace Rosalana\Core\Exceptions\Http;

class AppUnavailableException extends RosalanaHttpException {

    public function __construct(array $response = [])
    {
        parent::__construct($response, 'The app is currently unavailable. Please try again later.', 503);
    }

    public function getType(): string
    {
        return 'APP_UNAVAILABLE';
    }

    public function getErrors(): array
    {
        return [];
    }
}