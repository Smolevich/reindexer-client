<?php

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Entities\Index as IndexEntity;

class Index extends BaseService {
    public function create(IndexEntity $index, string $database, string $namespace) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/indexes',
            $this->version,
            $database,
            $namespace
        );

        return $this->client->request(
            'POST',
            $uri,
            json_encode($index->getBody()),
            $this->defaultHeaders
        );
    }

    public function get(string $database, string $namespace) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/indexes',
            $this->version,
            $database,
            $namespace
        );

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function delete(string $database, string $namespace, string $name) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/indexes/%s',
            $this->version,
            $database,
            $namespace,
            $name
        );

        return $this->client->request(
            'DELETE',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}
