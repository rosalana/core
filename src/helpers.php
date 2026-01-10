<?php

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Services\Actions\Inline;
use Rosalana\Core\Services\Actions\Runner;
use Rosalana\Core\Http\Responses\SuccessResponse;
use Rosalana\Core\Http\Responses\ErrorResponse;
use Rosalana\Core\Enums\ApiErrorType;

if (!function_exists('run')) {
    function run(Action $action): mixed
    {
        return Runner::run($action);
    }
}

if (!function_exists('action')) {
    function action(\Closure $callback): Action
    {
        return Inline::make($callback);
    }
}

if (!function_exists('ok')) {
    function ok(mixed $data = null, array $meta = []): SuccessResponse
    {
        return new SuccessResponse($data, $meta);
    }
}

if (!function_exists('error')) {
    function error(
        string $message = 'Unknown error',
        int $code = 500,
        ApiErrorType $type = ApiErrorType::UNKNOWN,
        array $errors = []
    ): ErrorResponse {
        return new ErrorResponse($message, $code, $type, $errors);
    }
}
