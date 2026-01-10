<?php

namespace Rosalana\Core\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler
{
    public static function convertExceptionToApiResponse(Throwable $e)
    {
        return match (true) {
            $e instanceof ValidationException => self::handleValidationException($e),
            $e instanceof ModelNotFoundException => self::handleModelNotFoundException($e),
            $e instanceof NotFoundHttpException => self::handleNotFoundHttpException($e),
            $e instanceof AuthenticationException => self::handleAuthenticationException($e),
            $e instanceof AccessDeniedHttpException => self::handleAccessDeniedException($e),
            $e instanceof HttpException => self::handleHttpException($e),
            default => self::handleGenericException($e),
        };
    }

    protected static function handleValidationException(ValidationException $e)
    {
        return error()
            ->validation($e->getMessage())
            ->errors($e->errors())();
    }

    protected static function handleModelNotFoundException(ModelNotFoundException $e)
    {
        $model = class_basename($e->getModel());
        $id = $e->getIds()[0] ?? 'unknown';

        return error()
            ->notFound("{$model} for identifier \"{$id}\" not found")();
    }

    protected static function handleNotFoundHttpException(NotFoundHttpException $e)
    {
        if ($e->getPrevious() instanceof ModelNotFoundException) {
            return self::handleModelNotFoundException($e->getPrevious());
        }

        return error()
            ->notFound($e->getMessage() ?: 'Not found')();
    }

    protected static function handleAuthenticationException(AuthenticationException $e)
    {
        return error()
            ->unauthorized($e->getMessage() ?: 'Unauthenticated')();
    }

    protected static function handleAccessDeniedException(AccessDeniedHttpException $e)
    {
        return error()
            ->forbidden($e->getMessage() ?: 'Forbidden')();
    }

    protected static function handleHttpException(HttpException $e)
    {
        $message = $e->getMessage() ?: 'An error occurred';
        $code = $e->getStatusCode();

        return match ($code) {
            400 => error()->badRequest($message)(),
            401 => error()->unauthorized($message)(),
            403 => error()->forbidden($message)(),
            404 => error()->notFound($message)(),
            422 => error()->validation($message)(),
            default => error($message, $code)->server()(),
        };
    }

    protected static function handleGenericException(Throwable $e)
    {
        return error()->server($e->getMessage() ?: 'Internal server error')();
    }
}
