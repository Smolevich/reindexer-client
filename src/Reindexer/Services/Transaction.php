<?php

declare(strict_types=1);

namespace Reindexer\Services;

use Reindexer\BaseService;
use Reindexer\Entities\SdlQuery;
use Reindexer\Response;

/**
 * HTTP transactions: begin() returns a tx_id, item operations and SQL/DSL
 * queries are accumulated under that id and applied atomically by commit()
 * (or discarded by rollback()). Mirrors the gRPC flow
 * beginTransaction() -> addTxItems() -> commit/rollbackTransaction().
 */
class Transaction extends BaseService
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

    public function begin(string $namespace): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/namespaces/%s/transactions/begin',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($namespace)
        );

        return $this->client->request(
            'POST',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function addItem(string $transactionId, array $data = [], array $precepts = []): Response
    {
        return $this->writeItems('POST', $transactionId, $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function updateItem(string $transactionId, array $data = [], array $precepts = []): Response
    {
        return $this->writeItems('PUT', $transactionId, $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function upsertItem(string $transactionId, array $data = [], array $precepts = []): Response
    {
        return $this->writeItems('PATCH', $transactionId, $data, $precepts);
    }

    /**
     * @param string[] $precepts e.g. ['id=serial()', 'updated_at=now()']
     */
    public function deleteItem(string $transactionId, array $data = [], array $precepts = []): Response
    {
        return $this->writeItems('DELETE', $transactionId, $data, $precepts);
    }

    /**
     * Adds an UPDATE/DELETE SQL query into the transaction.
     */
    public function sqlQuery(string $transactionId, string $query): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/transactions/%s/query?q=%s',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($transactionId),
            urlencode($query)
        );

        return $this->client->request(
            'GET',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    /**
     * Adds a DELETE Query-DSL query into the transaction.
     *
     * @param SdlQuery|array<string, mixed>|string $query Query-DSL entity, array or raw JSON string
     */
    public function deleteQuery(string $transactionId, SdlQuery|array|string $query): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/transactions/%s/query',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($transactionId)
        );

        if ($query instanceof SdlQuery) {
            $query = $query->getBody();
        }

        return $this->client->request(
            'DELETE',
            $uri,
            is_array($query) ? json_encode($query, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : $query,
            $this->defaultHeaders
        );
    }

    public function commit(string $transactionId): Response
    {
        return $this->finish('commit', $transactionId);
    }

    public function rollback(string $transactionId): Response
    {
        return $this->finish('rollback', $transactionId);
    }

    private function finish(string $action, string $transactionId): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/transactions/%s/%s',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($transactionId),
            $action
        );

        return $this->client->request(
            'POST',
            $uri,
            null,
            $this->defaultHeaders
        );
    }

    /**
     * @param string[] $precepts
     */
    private function writeItems(string $method, string $transactionId, array $data, array $precepts): Response
    {
        $uri = sprintf(
            '/api/%s/db/%s/transactions/%s/items',
            $this->version,
            $this->encodePathSegment($this->getDatabase()),
            $this->encodePathSegment($transactionId)
        );

        if ($precepts !== []) {
            $uri .= '?' . $this->buildPreceptsQuery($precepts);
        }

        return $this->client->request(
            $method,
            $uri,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $this->defaultHeaders
        );
    }

    /**
     * The server expects the precepts array in exploded form
     * (precepts=a&precepts=b), which http_build_query cannot produce.
     *
     * @param string[] $precepts
     */
    private function buildPreceptsQuery(array $precepts): string
    {
        return implode('&', array_map(
            static fn (string $precept): string => 'precepts=' . rawurlencode($precept),
            $precepts
        ));
    }
}
