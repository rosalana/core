<?php

namespace Rosalana\Core\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class SuccessResponse implements Responsable
{
    public function __construct(
        protected mixed $data = null,
        protected array $meta = [],
    ) {}

    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => $this->data,
            'meta' => $this->meta,
        ]);
    }

    public function __invoke()
    {
        return $this->toResponse(request());
    }
}
