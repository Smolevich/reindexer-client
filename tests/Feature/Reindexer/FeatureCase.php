<?php

declare(strict_types=1);

namespace Tests\Feature\Reindexer;

use PHPUnit\Framework\TestCase;
use Reindexer\Client\Api;
use Reindexer\Entities\Index;
use Reindexer\Enum\FieldType;
use Reindexer\Enum\IndexType;
use Reindexer\Services\Database;
use Reindexer\Services\Index as IndexService;
use Reindexer\Services\Item;
use Reindexer\Services\Namespaces;
use Reindexer\Services\Query;

/**
 * Base class for HTTP integration tests against a real reindexer server.
 *
 * Every test class gets its own database with a unique (parallel-safe) name,
 * dropped in tearDown.
 */
abstract class FeatureCase extends TestCase
{
    protected Api $api;
    protected string $database;
    protected Database $dbService;
    protected Namespaces $nsService;
    protected IndexService $indexService;
    protected Query $queryService;

    protected function setUp(): void
    {
        $host = (string) getenv('REINDEXER_HOST');
        if ($host === '') {
            $this->markTestSkipped('REINDEXER_HOST is not set.');
        }

        // http_errors disabled: error responses must be observable
        // (status + body) instead of being converted into transport errors.
        $this->api = new Api($host, ['http_errors' => false]);
        $this->database = uniqid('feature_' . strtolower(substr(static::class, strrpos(static::class, '\\') + 1)) . '_');

        $this->dbService = new Database($this->api);
        $this->nsService = new Namespaces($this->api);
        $this->nsService->setDatabase($this->database);
        $this->indexService = new IndexService($this->api);
        $this->queryService = new Query($this->api);
        $this->queryService->setDatabase($this->database);

        $response = $this->dbService->create($this->database);
        $this->assertSame(200, $response->getCode(), 'failed to create test database: ' . $response->getResponseBody());
    }

    protected function tearDown(): void
    {
        if (isset($this->dbService)) {
            $this->dbService->drop($this->database);
        }
    }

    protected function itemService(string $namespace): Item
    {
        $service = new Item($this->api);
        $service->setDatabase($this->database);
        $service->setNamespace($namespace);

        return $service;
    }

    protected function createNamespace(string $name, array $indexes = []): void
    {
        $response = $this->nsService->create($name, $indexes);
        $this->assertSame(200, $response->getCode(), 'failed to create namespace: ' . $response->getResponseBody());
    }

    protected function intPkIndex(string $name = 'id'): Index
    {
        return (new Index())
            ->setName($name)
            ->setJsonPaths([$name])
            ->setFieldType(FieldType::INT)
            ->setIndexType(IndexType::HASH)
            ->setIsPk(true);
    }

    protected function index(string $name, FieldType $fieldType, IndexType $indexType): Index
    {
        return (new Index())
            ->setName($name)
            ->setJsonPaths([$name])
            ->setFieldType($fieldType)
            ->setIndexType($indexType);
    }
}
