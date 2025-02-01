<?php

namespace Rosalana\Core\Clients;

use Illuminate\Support\Facades\Http;

trait Client
{
    public $url;
    public $secret;
    public $origin;

    protected $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    public function __construct()
    {
        $this->url = config('rosalana.auth.basecamp_url');
        $this->secret = config('rosalana.auth.app_secret');
        $this->origin = config('rosalana.auth.app_origin');

        $this->headers['X-App-Secret'] = $this->secret;
        $this->headers['Origin'] = $this->origin;
    }

    public function basecamp()
    {
        return $this;
    }

    protected function withAuth(string $token)
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;

        return $this;
    }

    protected function get(string $endpoint)
    {
        return Http::withHeaders($this->headers)->get($this->url . $endpoint);
    }

    protected function post(string $endpoint, array $data)
    {
        return Http::withHeaders($this->headers)->post($this->url . $endpoint, $data);
    }

    protected function put(string $endpoint, array $data)
    {
        return Http::withHeaders($this->headers)->put($this->url . $endpoint, $data);
    }

    protected function delete(string $endpoint)
    {
        return Http::withHeaders($this->headers)->delete($this->url . $endpoint);
    }

    protected function patch(string $endpoint, array $data)
    {
        return Http::withHeaders($this->headers)->patch($this->url . $endpoint, $data);
    }
}