<?php

namespace Rosalana\Core\Services\Basecamp\Services;

use Rosalana\Core\Services\Basecamp\Service;
use Rosalana\Core\Contracts\Basecamp\Model\ReadableExternalModel;

class AppsService extends Service implements ReadableExternalModel
{
    public function find(string|int $name)
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