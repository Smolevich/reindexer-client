<?php

declare(strict_types=1);

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Response;

class Item extends BaseService
{
    protected string $database;
    protected string $namespace;

    public function getDatabase(): string
    {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function getNamespace(): string
    {
        return $this->namespace ?? '';
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function add(array $data = [], array $precepts = []): Response
    {
        return $this->write('POST', $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function update(array $data = [], array $precepts = []): Response
    {
        return $this->write('PUT', $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function upsert(array $data = [], array $precepts = []): Response
    {
        return $this->write('PATCH', $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function delete(array $data = [], array $precepts = []): Response
    {
        return $this->write('DELETE', $data, $precepts);
    }

    /**
     * @param string[] $precepts
     */
    private function write(string $method, array $data, array $precepts): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($this->getNamespace())
        );

        if ($precepts !== []) {
            // The server expects the precepts array in exploded form
            // (precepts=a&precepts=b), which http_build_query cannot produce.
            $uri .= '?' . implode('&', array_map(
                static fn (string $precept): string => 'precepts=' . rawurlencode($precept),
                $precepts
            ));
        }

        return $this->client->request(
            $method,
            $uri,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $this->defaultHeaders
        );
    }

    public function get(int $limit = 0, int $offset = 0, string $sortField = '', string $sortOrder = ''): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/items',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($this->getNamespace())
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
