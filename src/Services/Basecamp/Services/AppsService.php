<?php

namespace Rosalana\Core\Services\Basecamp\Services;

use Rosalana\Core\Contracts\ReadOnlyExternalModel;
use Rosalana\Core\Services\Basecamp\Service;

class AppsService extends Service implements ReadOnlyExternalModel
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