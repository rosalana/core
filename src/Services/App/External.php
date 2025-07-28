<?php

namespace Rosalana\Core\Services\App;

use Rosalana\Core\Facades\Basecamp;
use Illuminate\Http\Client\Response;

class External
{
    public function self(): Response
    {
        return Basecamp::apps()->find(config('rosalana.basecamp.name', 'rosalana-app'));
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
