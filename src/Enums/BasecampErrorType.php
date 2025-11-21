<?php

namespace Rosalana\Core\Enums;

use Rosalana\Core\Exceptions\Http\RosalanaHttpException;

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

    case UNAVAILABLE = 'UNAVAILABLE';
    // more later...

    public function exception(): string
    {
        return match ($this) {
            self::UNAUTHORIZED => \Rosalana\Core\Exceptions\Http\BasecampUnauthorizedException::class,
            self::FORBIDDEN => \Rosalana\Core\Exceptions\Http\BasecampForbiddenException::class,
            self::NOT_FOUND => \Rosalana\Core\Exceptions\Http\BasecampNotFoundException::class,
            self::VALIDATION_ERROR => \Rosalana\Core\Exceptions\Http\BasecampValidationException::class,
            self::INTERNAL_SERVER_ERROR => \Rosalana\Core\Exceptions\Http\BasecampServerErrorException::class,
            self::BAD_REQUEST => \Rosalana\Core\Exceptions\Http\BasecampBadRequestException::class,
            
            self::GENERIC_ERROR => RosalanaHttpException::class,
            self::UNKNOWN => RosalanaHttpException::class,

            self::UNAVAILABLE => \Rosalana\Core\Exceptions\Http\BasecampUnavailableException::class,
        };
    }

    public function throw(array $response): RosalanaHttpException
    {
        throw new ($this->exception())($response);
    }
}