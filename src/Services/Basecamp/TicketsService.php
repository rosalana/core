<?php

namespace Rosalana\Core\Services\Basecamp;

/**
 *  Zatím jen blueprint pro tickets service
 */
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