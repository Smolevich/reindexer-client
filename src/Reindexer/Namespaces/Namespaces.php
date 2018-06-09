<?php

namespace Reindexer\Namespaces;

use Reindexer\Entity;
use Reindexer\Indexes\Index;

class Namespaces extends Entity {

    public function getList(string $database, string $sortOrder = 'asc') {
        $uri = sprintf('/api/%s/db/%s/namespaces', $this->version, $database);

        if (!empty($sortOrder)) {
            $uri .= '?sort_order='. $sortOrder;
        }

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function create(string $database, string $name, array $indexes) {
        $body = [
            'name' => $name,
            'storage' => [
                'enabled' => true
            ],
            'indexes' => []
        ];

        foreach ($indexes as $index) {
            if ($index instanceof Index) {
                $body['indexes'][] = $index->getBody();
            }
        }
        $uri = sprintf('/api/%s/db/%s/namespaces', $this->version, $database);

        return $this->client->request(
            'POST',
            $uri,
            json_encode($body),
            $this->defaultHeaders
        );
    }

    public function delete(string $database, string $name) {
        $uri = sprintf('/api/%s/db/%s/namespaces/%s', $this->version, $database, $name);

        return $this->client->request(
            'DELETE',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}