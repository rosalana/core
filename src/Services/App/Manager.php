<?php

namespace Rosalana\Core\Services\App;

class Manager
{
    public function hooks()
    {
        // hooks service
    }

    public function context()
    {
        // context of the application (cached from the basecamp)
    }

    public function fire()
    {
        // fire App lifecycle events
    }

    public function shutdown()
    {
        // shutdown the application
    }

    public function init()
    {
        // initialize the application
    }

    public function pipeline()
    {
        // start a pipeline for the application
    }

    public function config()
    {
        // get the rosalana configuration
    }


    // doplnitelné o vlastní metody z jiných balíčků

}