<?php

namespace Rosalana\Core\Services\Basecamp;

abstract class Service
{
    protected ?Manager $manager = null;

    public function setManagerContext(Manager $manager)
    {
        $this->manager = $manager;
    }
    
}