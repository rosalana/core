<?php

namespace Rosalana\Core\Exceptions\Http\Api;

use Exception;

class ApiFilterHelpException extends Exception
{
    /**
     * @var array<mixed>
     */
    protected array $data;

    /**
     * Create a new exception instance.
     *
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct('API Filter Help', code: 0, previous: null);
        $this->data = $data;
    }

    /**
     * Get the data.
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
