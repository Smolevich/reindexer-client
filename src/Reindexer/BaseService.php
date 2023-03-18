<?php

namespace Reindexer;

use Reindexer\Client\BaseApi;

abstract class BaseService {
    protected BaseApi $client;
    protected string $version = 'v1';
    protected $mapJsonFields = [];

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

    public function setHeaders(array $headers): self {
        $this->defaultHeaders = $headers;

        return $this;
    }

    public function addHeaders(array $headers): self {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $this;
    }
}
