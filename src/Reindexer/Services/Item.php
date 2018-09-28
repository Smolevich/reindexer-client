<?php

namespace Reindexer\Services;

use Reindexer\BaseService;

class Item extends BaseService {
    protected $database;
    protected $namespace;

    public function getDatabase(): string {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void {
        $this->database = $database;
    }

    public function getNamespace(): string {
        return $this->namespace ?? '';
    }

    public function setNamespace(string $namespace): void {
        $this->namespace = $namespace;
    }

    public function add(array $data = []) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        return $this->client->request(
            'POST',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function update(array $data = []) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        return $this->client->request(
            'PUT',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function delete(array $data = []) {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );

        return $this->client->request(
            'DELETE',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function get(int $limit = 0, int $offset = 0, string $sortField = '', string $sortOrder = '') {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->getDatabase(),
            $this->getNamespace()
        );
        $params = [];

        if ($limit) {
            $params['limit'] = $limit;
        }

        if ($offset) {
            $params['offset'] = $offset;
        }

        if ($sortField) {
            $params['sort_field'] = $sortField;
        }

        if ($sortOrder) {
            $params['sort_order'] = $sortOrder;
        }

        if ($params) {
            $uri .= '?' . http_build_query($params);
        }

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }
}
