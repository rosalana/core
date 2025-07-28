<?php

namespace Rosalana\Core\Services\App;

use Rosalana\Core\Facades\Basecamp;
use Illuminate\Http\Client\Response;
use Rosalana\Core\Facades\App;

class External
{
    public function self(): Response
    {
        return Basecamp::apps()->find(App::slug());
    }

    public function list(): Response
    {
        return Basecamp::apps()->all();
    }

    public function find(string $name): Response
    {
        return Basecamp::apps()->find($name);
    }
}
