<?php

namespace Rosalana\Core\Http\Responses;

use Rosalana\Core\Enums\ApiErrorType;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ErrorResponse implements Responsable
{
    public function __construct(
        protected string $message = 'Unknown error',
        protected int $code = 500,
        protected ApiErrorType $type = ApiErrorType::UNKNOWN,
        protected array $errors = [],
    ) {}

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function code(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function type(ApiErrorType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function errors(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);

        return $this;
    }

    public function badRequest(string $message = 'Bad request'): self
    {
        $this->message = $message;
        $this->code = 400;
        $this->type = ApiErrorType::BAD_REQUEST;

        return $this;
    }

    public function generic(string $message = 'An error occurred'): self
    {
        $this->message = $message;
        $this->code = 400;
        $this->type = ApiErrorType::GENERIC_ERROR;

        return $this;
    }

    public function unauthorized(string $message = 'Unauthorized'): self
    {
        $this->message = $message;
        $this->code = 401;
        $this->type = ApiErrorType::UNAUTHORIZED;

        return $this;
    }

    public function notFound(string $message = 'Not found'): self
    {
        $this->message = $message;
        $this->code = 404;
        $this->type = ApiErrorType::NOT_FOUND;

        return $this;
    }

    public function forbidden(string $message = 'Forbidden'): self
    {
        $this->message = $message;
        $this->code = 403;
        $this->type = ApiErrorType::FORBIDDEN;

        return $this;
    }

    public function validation(string $message = 'Validation error', array $errors = []): self
    {
        $this->message = $message;
        $this->code = 422;
        $this->type = ApiErrorType::VALIDATION_ERROR;

        if (!empty($errors)) {
            $this->errors = array_merge($this->errors, $errors);
        }

        return $this;
    }

    public function server(string $message = 'Internal server error'): self
    {
        $this->message = $message;
        $this->code = 500;
        $this->type = ApiErrorType::INTERNAL_SERVER_ERROR;

        return $this;
    }


    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->message,
            'code' => $this->code,
            'type' => $this->type->value,
            'errors' => $this->errors,
        ])->setStatusCode(200);
    }

    public function __invoke()
    {
        return $this->toResponse(request());
    }
}
