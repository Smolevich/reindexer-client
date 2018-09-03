<?php

namespace Reindexer\Services;

use Reindexer\BaseService;

class Query extends BaseService {
    public function createByHttpGet(string $database, string $query) {
        $uri = sprintf('/api/%s/db/%s/query?q=%s', $this->version, $database, urlencode($query));

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function createSqlQueryByHttpPost(string $database, string $query) {
        $uri = sprintf('/api/%s/db/%s/sqlquery', $this->version, $database);
        return $this->client->request(
            'POST',
            $uri,
            $query,
            $this->defaultHeaders
        );
    }

    public function createSdlQueryByHttpPost(string $database, string $query) {
        $uri = sprintf('/api/%s/db/%s/query', $this->version, $database);
        return $this->client->request(
            'POST',
            $uri,
            json_encode($query),
            $this->defaultHeaders
        );
    }
}
