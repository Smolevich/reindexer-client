<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\TestCase;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Transport\Grpc\GrpcClient;

/**
 * Base class for gRPC integration tests against a real reindexer server.
 * Namespace names are unique per test (prefix + uniqid) so suites can run
 * in parallel against a shared server.
 */
abstract class GrpcFeatureCase extends TestCase
{
    protected const DB_NAME = 'grpc_feature_db';

    protected GrpcClient $client;

    /** @var string[] namespaces created by the test, dropped in tearDown */
    private array $createdNamespaces = [];

    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            $this->markTestSkipped('The "grpc" extension is not loaded.');
        }

        $target = (string) getenv('REINDEXER_GRPC_TARGET');
        if ($target === '') {
            $this->markTestSkipped('REINDEXER_GRPC_TARGET is not set.');
        }

        $this->client = new GrpcClient($target);

        try {
            $this->client->connect(static::DB_NAME);
        } catch (GrpcException) {
            $this->client->createDatabase(static::DB_NAME);
            $this->client->connect(static::DB_NAME);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdNamespaces as $nsName) {
            try {
                $this->client->dropNamespace($nsName);
            } catch (GrpcException) {
                // already dropped by the test itself
            }
        }
    }

    protected function uniqueNs(string $prefix): string
    {
        $name = uniqid($prefix . '_');
        $this->createdNamespaces[] = $name;

        return $name;
    }

    /**
     * Opens a namespace with an int PK index "id".
     */
    protected function createNamespaceWithPk(string $prefix): string
    {
        $nsName = $this->uniqueNs($prefix);
        $this->client->openNamespace($nsName);
        $this->client->addIndex($nsName, [
            'name' => 'id',
            'fieldType' => 'int',
            'indexType' => 'hash',
            'isPk' => true,
        ]);

        return $nsName;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function selectAll(string $nsName, string $orderBy = 'id'): array
    {
        return iterator_to_array(
            $this->client->execSql(sprintf('SELECT * FROM %s ORDER BY %s', $nsName, $orderBy)),
            false
        );
    }
}
