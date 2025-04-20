<?php

namespace Rosalana\Core\Services\Basecamp;

class AppsService extends Service
{
    public function find(string $name)
    {
        return $this->manager
            ->withAuth()
            ->get('apps/' . $name);
    }
}