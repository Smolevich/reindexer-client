<?php

namespace Reindexer\Database;

use Reindexer\BaseClient;
use Reindexer\Entity;

class Database extends Entity {
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

    public function drop() {
    }
}