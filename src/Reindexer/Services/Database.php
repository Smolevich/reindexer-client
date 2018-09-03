<?php

namespace Reindexer\Services;

use Reindexer\BaseService;

class Database extends BaseService {
    public function create(string $name) {
        $uri = sprintf('/api/%s/db', $this->version);
        return $this->client->request(
            'POST',
            $uri,
            json_encode([
                'name' => $name
            ]),
            $this->defaultHeaders
        );
    }

    public function getList() {
        $uri = sprintf('/api/%s/db', $this->version);
        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function drop(string $name) {
        $uri = sprintf('/api/%s/db/%s', $this->version, $name);
        return $this->client->request(
            'DELETE',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}
