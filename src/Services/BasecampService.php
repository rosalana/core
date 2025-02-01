<?php

namespace Rosalana\Core\Services;

abstract class BasecampService
{
    protected ?BasecampManager $manager = null;

    public function setManagerContext(BasecampManager $manager)
    {
        $this->manager = $manager;
    }
    
}