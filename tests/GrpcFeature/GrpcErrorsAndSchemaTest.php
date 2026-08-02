<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;
use Reindexer\Exceptions\GrpcException;

/**
 * ErrorResponse mapping for bad requests + index/namespace management RPCs.
 */
#[Group('GrpcFeature')]
final class GrpcErrorsAndSchemaTest extends GrpcFeatureCase
{
    public function testSelectFromMissingNamespaceThrowsWithServerCode(): void
    {
        try {
            iterator_to_array($this->client->execSql('SELECT * FROM grpc_missing_' . uniqid()), false);
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            $this->assertNotSame(0, $e->getCode());
            $this->assertStringContainsString('Reindexer gRPC error', $e->getMessage());
        }
    }

    public function testInvalidSqlThrows(): void
    {
        try {
            iterator_to_array($this->client->execSql('SELEKT nonsense'), false);
            $this->fail('Expected GrpcException');
        } catch (GrpcException $e) {
            $this->assertNotSame(0, $e->getCode());
            $this->assertStringContainsString('rror', $e->getMessage());
        }
    }

    public function testEnumDatabasesContainsConnectedDb(): void
    {
        $this->assertContains(static::DB_NAME, $this->client->enumDatabases());
    }

    public function testEnumNamespacesFilters(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_enum');

        $all = $this->client->enumNamespaces();
        $this->assertContains($nsName, $all);

        $filtered = $this->client->enumNamespaces($nsName);
        $this->assertSame([$nsName], $filtered);
    }

    public function testUpdateIndexChangesDefinition(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_updidx');
        $this->client->addIndex($nsName, [
            'name' => 'rank',
            'fieldType' => 'int',
            'indexType' => 'hash',
        ]);

        // change index type hash -> tree
        $this->client->updateIndex($nsName, [
            'name' => 'rank',
            'fieldType' => 'int',
            'indexType' => 'tree',
        ]);

        $this->client->modifyItems($nsName, [
            ['id' => 1, 'rank' => 30],
            ['id' => 2, 'rank' => 10],
            ['id' => 3, 'rank' => 20],
        ]);

        $items = iterator_to_array(
            $this->client->execSql(sprintf('SELECT * FROM %s WHERE rank > 10 ORDER BY rank', $nsName)),
            false
        );
        $this->assertSame([20, 30], array_column($items, 'rank'));
    }

    public function testDropIndex(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_dropidx');
        $this->client->addIndex($nsName, [
            'name' => 'extra',
            'fieldType' => 'string',
            'indexType' => 'hash',
        ]);

        $this->client->dropIndex($nsName, ['name' => 'extra']);

        // adding it again must succeed if the drop actually happened
        $this->client->addIndex($nsName, [
            'name' => 'extra',
            'fieldType' => 'string',
            'indexType' => 'hash',
        ]);
        $this->addToAssertionCount(1);
    }

    public function testAddDuplicateIndexWithDifferentTypeThrows(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_dupidx');
        $this->client->addIndex($nsName, [
            'name' => 'field_a',
            'fieldType' => 'string',
            'indexType' => 'hash',
        ]);

        $this->expectException(GrpcException::class);
        $this->client->addIndex($nsName, [
            'name' => 'field_a',
            'fieldType' => 'int',
            'indexType' => 'tree',
        ]);
    }

    public function testCloseNamespaceThenReopen(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_close');
        $this->client->modifyItems($nsName, [['id' => 1, 'name' => 'persisted']]);

        $this->client->closeNamespace($nsName);
        $this->client->openNamespace($nsName);

        $items = $this->selectAll($nsName);
        $this->assertSame('persisted', $items[0]['name']);
    }

    public function testDropNamespaceMakesItInvisible(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_dropns');
        $this->client->dropNamespace($nsName);

        $this->assertNotContains($nsName, $this->client->enumNamespaces());
    }

    public function testTtlIndexIsAccepted(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_ttl');

        // ttl index type is supported by the gRPC transport (not by the HTTP enum)
        $this->client->addIndex($nsName, [
            'name' => 'expires_at',
            'fieldType' => 'int64',
            'indexType' => 'ttl',
            'expireAfter' => 3600,
        ]);

        $this->client->modifyItems($nsName, [['id' => 1, 'expires_at' => time() + 3600]]);
        $this->assertCount(1, $this->selectAll($nsName));
    }
}
