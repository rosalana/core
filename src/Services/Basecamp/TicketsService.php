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
            ->withAuth()
            ->get('.well-known/tickets');
    }

    public function create(array $data)
    {
        return $this->manager
            ->withAuth()
            ->post('', $data);
    }
}