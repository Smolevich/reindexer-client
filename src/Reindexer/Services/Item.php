<?php

namespace Reindexer\Services;

use Reindexer\BaseService;

class Item extends BaseService {

    public function add(string $database, string $namespace, array $data = []) {
        $uri = sprintf('/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $database,
            $namespace
        );

        return $this->client->request(
            'POST',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function update(string $database, string $namespace, array $data = []) {
        $uri = sprintf('/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $database,
            $namespace
        );

        return $this->client->request(
            'PUT',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function delete(string $database, string $namespace, array $data = []) {
        $uri = sprintf('/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $database,
            $namespace
        );

        return $this->client->request(
            'DELETE',
            $uri,
            json_encode($data),
            $this->defaultHeaders
        );
    }

    public function get(string $database, string $namespace, int $limit = 0, int $offset = 0, string $sortField = '', string $sortOrder = '') {
        $uri = sprintf('/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $database,
            $namespace
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