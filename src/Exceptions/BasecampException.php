<?php

namespace Rosalana\Core\Exceptions;

use Exception;

class BasecampException extends Exception
{
    protected array $response;

    public function __construct(array $response)
    {
        $this->response = $response;

        parent::__construct(
            $response['message'] ?? 'Unknown error', 
            $response['code'] ?? 0, 
            null
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

class BasecampValidationException extends BasecampException {}
class BasecampUnauthorizedException extends BasecampException {}
class BasecampForbiddenException extends BasecampException {}
class BasecampNotFoundException extends BasecampException {}
class BasecampServerErrorException extends BasecampException {}
class BasecampBadRequestException extends BasecampException {}
