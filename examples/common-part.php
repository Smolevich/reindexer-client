<?php

use Reindexer\Client\Api;
use Reindexer\LoggerInterface;
use Reindexer\Response;

class Configuration
{
    protected $api;
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getApi(): Api
    {
        if (empty($this->api)) {
            $this->api = new Api($this->config['endpoint'], $this->config['client_config']);
            $this->api->setLogger(new ApiLogger());
        }
        return $this->api = $this->api ?:
            new Api($this->config['endpoint'], $this->config['client_config']);
    }
}

class ApiLogger implements LoggerInterface
{
    public function logResponse(Response $response): void
    {
        echo sprintf(
            '==== api request info ===='.PHP_EOL.
            'Content-Type: %s'.PHP_EOL.
            'Date: %s'.PHP_EOL.
            'Http_code: %s'.PHP_EOL.
            'Url: %s'.PHP_EOL,
            $response->getResponseHeaders()['Content-Type'][0] ?? '',
            $response->getResponseHeaders()['Date'][0] ?? '',
            $response->getCode(),
            $response->getRequest()->getUri()->__toString()
        );
    }
}
