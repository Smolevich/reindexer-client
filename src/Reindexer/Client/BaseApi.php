<?php

namespace Reindexer\Client;

use Reindexer\Response;

abstract class BaseApi
{
    protected $host;
    protected $config;

    public function __construct(string $host, array $config = [])
    {
        $this->host = $host;
        $this->config = $config;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host)
    {
        $this->host = $host;
    }

    abstract public function request(string $method, string $uri, string $body = null, array $headers = []): Response;
}
