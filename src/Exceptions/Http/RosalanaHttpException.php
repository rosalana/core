<?php

namespace Rosalana\Core\Exceptions\Http;

use Exception;

class RosalanaHttpException extends Exception
{
    protected array $response;

    public function __construct(array $response, string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if (empty($message) && !empty($response['message'])) {
            $message = $response['message'];
        }
        if (empty($code) && !empty($response['code'])) {
            $code = $response['code'];
        }

        $this->response = $response;

        parent::__construct(
            $message ?? 'Unknown error',
            $code ?? 0,
            $previous
        );
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getType(): string
    {
        return $this->response['type'] ?? 'UNKNOWN';
    }

    public function getErrors(): array
    {
        return $this->response['errors'] ?? [];
    }
}

