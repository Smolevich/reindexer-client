<?php

namespace Reindexer;

use Reindexer\Client\BaseApi;

abstract class Entity {
    protected $client;
    protected $version = 'v1';

    protected $defaultHeaders = [
        'Content-Type' => 'application/json;charset=utf-8'
    ];

    public function __construct(BaseApi $client) {
        $this->client = $client;
    }

    public function setVersion(string $version): self {
        $this->version = $version;

        return $this;
    }
}