<?php

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Response;

class Namespaces extends BaseService {
    protected $database;

    public function getDatabase(): string {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void {
        $this->database = $database;
    }

    public function getList(string $sortOrder = 'asc'): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces',
            $this->version,
            $this->getDatabase()
        );

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

    public function create(string $name, array $indexes = []): Response {
        $body = [
            'name' => $name,
            'storage' => [
                'enabled' => true
            ],
            'indexes' => []
        ];

        foreach ($indexes as $index) {
            if ($index instanceof IndexEntity) {
                $body['indexes'][] = $index->getBody();
            }
        }
        $uri = sprintf(
            '/api/%s/db/%s/namespaces',
            $this->version,
            $this->getDatabase()
        );

        return $this->client->request(
            'POST',
            $uri,
            json_encode($body),
            $this->defaultHeaders
        );
    }

    public function drop(string $name): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s',
            $this->version,
            $this->getDatabase(),
            $name
        );

        return $this->client->request(
            'DELETE',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function get(string $name): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s',
            $this->version,
            $this->getDatabase(),
            $name
        );

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function truncate(string $name): Response {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/truncate',
            $this->version,
            $this->getDatabase(),
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
