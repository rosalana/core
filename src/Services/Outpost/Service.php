<?php

namespace Rosalana\Core\Services\Outpost;

abstract class Service
{
    protected ?Manager $manager = null;

    public function setManagerContext(Manager $manager)
    {
        $this->manager = $manager;
    }
    
}