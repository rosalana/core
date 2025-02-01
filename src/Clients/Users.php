<?php

namespace Rosalana\Core\Clients;

class Users
{
    use Client;

    public function login($email, $password)
    {
        $this->basecamp()->get('/login');
    }
}