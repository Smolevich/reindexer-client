<?php

namespace Reindexer\Services;

use GuzzleHttp\Psr7\Uri;
use Reindexer\BaseService;
use Reindexer\Entities\Index as IndexEntity;
use Reindexer\Response;

class Namespaces extends BaseService
{
    protected string $database;

    public function getDatabase(): string
    {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function getList(string $sortOrder = 'asc'): Response
    {
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

    public function create(string $name, array $indexes = []): Response
    {
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

    public function drop(string $name): Response
    {
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

    public function get(string $name): Response
    {
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

    public function truncate(string $name): Response
    {
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

    public function rename(string $oldName, string $newName): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/rename/%s',
            $this->version,
            $this->getDatabase(),
            $oldName,
            $newName
        );

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function getMetaList(
        string $name,
        int $limit = 0,
        int $offset = 0,
        string $sortOrder = '',
        bool $withValues = false
    ): Response {
        $uri = new Uri(
            sprintf(
                '/api/%s/db/%s/namespaces/%s/metalist',
                $this->version,
                $this->getDatabase(),
                $name
            )
        );

        if ($limit) {
            $params['limit'] = $limit;
        }

        if ($offset) {
            $params['offset'] = $offset;
        }

        $params['with_values'] = $withValues ? 'true' : 'false';

        if ($sortOrder) {
            $params['sort_order'] = $sortOrder;
        }

        if ($params) {
            $uri = Uri::withQueryValues($uri, $params);
        }

        return $this->client->request(
            'GET',
            (string)$uri,
            null,
            $this->defaultHeaders
        );
    }

    public function addMetaDataKey(string $name, string $key, string $value): Response
    {
        $uri = new Uri(
            sprintf(
                '/api/%s/db/%s/namespaces/%s/metabykey',
                $this->version,
                $this->getDatabase(),
                $name
            )
        );

        return $this->client->request(
            'PUT',
            (string)$uri,
            json_encode(['key' => $key, 'value' => $value]),
            $this->defaultHeaders
        );
    }

    public function getMetaDataKey(string $name, string $key): Response
    {
        $uri = new Uri(
            sprintf(
                '/api/%s/db/%s/namespaces/%s/metabykey/%s',
                $this->version,
                $this->getDatabase(),
                $name,
                $key
            )
        );

        return $this->client->request(
            'PUT',
            (string)$uri,
            null,
            $this->defaultHeaders
        );
    }

    public function schema(string $name, array $config): Response
    {
        $uri = new Uri(
            sprintf(
                '/api/%s/db/%s/namespaces/%s/schema',
                $this->version,
                $this->getDatabase(),
                $name
            )
        );

        return $this->client->request(
            'PUT',
            (string)$uri,
            json_encode($config),
            $this->defaultHeaders
        );
    }
}
