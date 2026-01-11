<?php

namespace Rosalana\Core\Services\Basecamp\Services;

use Rosalana\Core\Services\Basecamp\Service;

class TicketsService extends Service
{
    public function list()
    {
        return $this->manager
            ->get('.well-known/tickets');
    }

    public function create(array $data)
    {
        return $this->manager
            ->withAuth()
            ->post('tickets', $data);
    }
}