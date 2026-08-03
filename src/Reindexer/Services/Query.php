<?php

declare(strict_types=1);

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Entities\SdlQuery;
use Reindexer\Response;

class Query extends BaseService
{
    public string $database = '';

    public function getDatabase(): string
    {
        return $this->database ?? '';
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function createByHttpGet(string $query): Response
    {
        $uri = sprintf('/api/%s/db/%s/query?q=%s', $this->version, $this->encodePathSegment($this->getDatabase()), urlencode($query));

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    public function createSqlQueryByHttpPost(string $query): Response
    {
        $uri = sprintf('/api/%s/db/%s/sqlquery', $this->version, $this->encodePathSegment($this->getDatabase()));

        return $this->client->request(
            'POST',
            $uri,
            $query,
            $this->defaultHeaders
        );
    }

    /**
     * @param SdlQuery|array<string, mixed>|string $query Query-DSL entity, array or raw JSON string
     */
    public function createSdlQueryByHttpPost(SdlQuery|array|string $query): Response
    {
        return $this->sendSdlQuery('POST', $query);
    }

    /**
     * Updates documents by a Query-DSL query (PUT /query). The DSL should
     * contain update_fields and/or drop_fields.
     *
     * @param SdlQuery|array<string, mixed>|string $query Query-DSL entity, array or raw JSON string
     */
    public function updateSdlQueryByHttpPut(SdlQuery|array|string $query): Response
    {
        return $this->sendSdlQuery('PUT', $query);
    }

    /**
     * Deletes documents matching a Query-DSL query (DELETE /query).
     *
     * @param SdlQuery|array<string, mixed>|string $query Query-DSL entity, array or raw JSON string
     */
    public function deleteSdlQueryByHttpDelete(SdlQuery|array|string $query): Response
    {
        return $this->sendSdlQuery('DELETE', $query);
    }

    /**
     * @param SdlQuery|array<string, mixed>|string $query
     */
    private function sendSdlQuery(string $method, SdlQuery|array|string $query): Response
    {
        $uri = sprintf('/api/%s/db/%s/query', $this->version, $this->encodePathSegment($this->getDatabase()));

        if ($query instanceof SdlQuery) {
            $query = $query->getBody();
        }

        return $this->client->request(
            $method,
            $uri,
            is_array($query) ? json_encode($query, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : $query,
            $this->defaultHeaders
        );
    }
}
