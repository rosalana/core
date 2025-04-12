<?php

namespace Rosalana\Core\Exceptions;

enum BasecampErrorType: string
{
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    case BAD_REQUEST = 'BAD_REQUEST';

    case GENERIC_ERROR = 'GENERIC_ERROR';
    case UNKNOWN = 'UNKNOWN';
    // more later...

    public function exception(): string
    {
        return match ($this) {
            self::UNAUTHORIZED => \Rosalana\Core\Exceptions\BasecampUnauthorizedException::class,
            self::FORBIDDEN => \Rosalana\Core\Exceptions\BasecampForbiddenException::class,
            self::NOT_FOUND => \Rosalana\Core\Exceptions\BasecampNotFoundException::class,
            self::VALIDATION_ERROR => \Rosalana\Core\Exceptions\BasecampValidationException::class,
            self::INTERNAL_SERVER_ERROR => \Rosalana\Core\Exceptions\BasecampServerErrorException::class,
            self::BAD_REQUEST => \Rosalana\Core\Exceptions\BasecampBadRequestException::class,

            self::GENERIC_ERROR => \Rosalana\Core\Exceptions\BasecampException::class,
            self::UNKNOWN => \Rosalana\Core\Exceptions\BasecampException::class,
            default => \Rosalana\Core\Exceptions\BasecampException::class,
        };
    }

    public function throw(array $response): BasecampException
    {
        throw new ($this->exception)($response);
    }
}