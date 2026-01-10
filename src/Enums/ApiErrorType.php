<?php

namespace Rosalana\Core\Enums;

enum ApiErrorType: string
{
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    case BAD_REQUEST = 'BAD_REQUEST';
    
    case GENERIC_ERROR = 'GENERIC_ERROR';
    case UNKNOWN = 'UNKNOWN';
}