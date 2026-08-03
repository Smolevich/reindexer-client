<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;
use Reindexer\Exceptions\GrpcException;
use Reindexer\Transport\Grpc\GrpcClient;

/**
 * connect()/createDatabase() semantics and argument variants of
 * enumNamespaces()/select() against a real server.
 */
#[Group('GrpcFeature')]
final class GrpcConnectionTest extends GrpcFeatureCase
{
    public function testConnectToExistingDatabaseBindsChannel(): void
    {
        $target = (string) getenv('REINDEXER_GRPC_TARGET');
        $client = new GrpcClient($target);

        $client->connect(static::DB_NAME);

        // the binding is proven by a working namespaced RPC
        $this->assertIsArray($client->enumNamespaces());
        $this->assertContains(static::DB_NAME, $client->enumDatabases());
    }

    public function testConnectToMissingDatabaseThrowsNotFound(): void
    {
        $client = new GrpcClient((string) getenv('REINDEXER_GRPC_TARGET'));

        try {
            $client->connect('grpc_missing_db_' . uniqid());
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            // v5.15.0 answers "Database ... not found" with a non-zero code
            $this->assertNotSame(0, $e->getCode());
            $this->assertStringContainsString('not found', $e->getMessage());
        }

        // the failed connect must not bind the channel
        $this->expectException(GrpcException::class);
        $this->expectExceptionMessage('Not connected');
        $client->enumNamespaces();
    }

    public function testCreateDatabaseThenConnect(): void
    {
        $dbName = uniqid('grpc_created_db_');
        $client = new GrpcClient((string) getenv('REINDEXER_GRPC_TARGET'));

        $client->createDatabase($dbName);
        $this->assertContains($dbName, $client->enumDatabases());

        $client->connect($dbName);
        $this->assertSame([], $client->enumNamespaces());
    }

    public function testCreateDatabaseIsIdempotent(): void
    {
        $dbName = uniqid('grpc_dup_db_');
        $client = new GrpcClient((string) getenv('REINDEXER_GRPC_TARGET'));

        $client->createDatabase($dbName);
        // v5.15.0 treats a repeated CreateDatabase of the same name as a no-op
        $client->createDatabase($dbName);

        $this->assertSame(
            1,
            count(array_keys($client->enumDatabases(), $dbName, true)),
            'duplicate createDatabase must not produce a second database'
        );
    }

    public function testEnumNamespacesWithClosedIncludesClosedNamespace(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_closed_enum');
        $this->client->closeNamespace($nsName);

        $this->assertContains($nsName, $this->client->enumNamespaces('', true));
    }

    public function testSelectAcceptsRawJsonStringDsl(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_strdsl');
        $this->client->modifyItems($nsName, [
            ['id' => 1, 'name' => 'keep'],
            ['id' => 2, 'name' => 'skip'],
        ]);

        $dsl = sprintf(
            '{"namespace":"%s","filters":[{"field":"id","cond":"EQ","value":1}]}',
            $nsName
        );
        $items = iterator_to_array($this->client->select($dsl), false);

        $this->assertSame([['id' => 1, 'name' => 'keep']], $items);
    }
}
