<?php

namespace Rosalana\Core\Services\Basecamp\Services;

use Rosalana\Core\Services\Basecamp\Service;

class AppsService extends Service
{
    public function find(string $name)
    {
        return $this->manager
            ->withAuth()
            ->get('apps/' . $name);
    }

    public function all()
    {
        return $this->manager
            ->withAuth()
            ->get('apps');
    }
}