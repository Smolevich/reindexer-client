<?php

namespace Reindexer\Services;

use Reindexer\BaseService;

class Query extends BaseService {
    public string $database;

    public function getDatabase(): string {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void {
        $this->database = $database;
    }

    public function createByHttpGet(string $query) {
        $uri = sprintf('/api/%s/db/%s/query?q=%s', $this->version, $this->getDatabase(), urlencode($query));

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function createSqlQueryByHttpPost(string $query) {
        $uri = sprintf('/api/%s/db/%s/sqlquery', $this->version, $this->getDatabase());

        return $this->client->request(
            'POST',
            $uri,
            $query,
            $this->defaultHeaders
        );
    }

    public function createSdlQueryByHttpPost(string $query) {
        $uri = sprintf('/api/%s/db/%s/query', $this->version, $this->getDatabase());

        return $this->client->request(
            'POST',
            $uri,
            json_encode($query),
            $this->defaultHeaders
        );
    }
}
