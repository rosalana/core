<?php

namespace Rosalana\Core\Enums;

use Rosalana\Core\Exceptions\Http\RosalanaHttpException;

enum HttpAppErrorType: string
{
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    case BAD_REQUEST = 'BAD_REQUEST';

    case GENERIC_ERROR = 'GENERIC_ERROR';
    case UNKNOWN = 'UNKNOWN';

    case UNREACHABLE = 'UNREACHABLE';
    case UNAVAILABLE = 'UNAVAILABLE';
    // more later...

    public function exception(): string
    {
        return match ($this) {
            self::UNAUTHORIZED => \Rosalana\Core\Exceptions\Http\AppUnauthorizedException::class,
            self::FORBIDDEN => \Rosalana\Core\Exceptions\Http\AppForbiddenException::class,
            self::NOT_FOUND => \Rosalana\Core\Exceptions\Http\AppNotFoundException::class,
            self::VALIDATION_ERROR => \Rosalana\Core\Exceptions\Http\AppValidationException::class,
            self::INTERNAL_SERVER_ERROR => \Rosalana\Core\Exceptions\Http\AppServerErrorException::class,
            self::BAD_REQUEST => \Rosalana\Core\Exceptions\Http\AppBadRequestException::class,

            self::GENERIC_ERROR => RosalanaHttpException::class,
            self::UNKNOWN => RosalanaHttpException::class,

            self::UNREACHABLE => \Rosalana\Core\Exceptions\Http\AppUnreachableException::class,
            self::UNAVAILABLE => \Rosalana\Core\Exceptions\Http\AppUnavailableException::class,
        };
    }

    public function throw(array $response): RosalanaHttpException
    {
        throw new ($this->exception())($response);
    }
}