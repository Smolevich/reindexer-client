<?php

declare(strict_types=1);

namespace Tests\GrpcFeature;

use PHPUnit\Framework\Attributes\Group;

/**
 * Namespace metadata, JSON/protobuf schemas and AddNamespace over gRPC
 * against a real server.
 */
#[Group('GrpcFeature')]
final class GrpcMetaSchemaTest extends GrpcFeatureCase
{
    public function testMetaPutGetEnumDeleteCycle(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_meta');

        $this->client->putMeta($nsName, 'schema_version', '42');
        $this->client->putMeta($nsName, 'описание', 'мета со значением 🚀');

        $this->assertSame('42', $this->client->getMeta($nsName, 'schema_version'));
        $this->assertSame('мета со значением 🚀', $this->client->getMeta($nsName, 'описание'));

        $keys = $this->client->enumMeta($nsName);
        sort($keys);
        $this->assertSame(['schema_version', 'описание'], $keys);

        $this->client->deleteMeta($nsName, 'schema_version');
        $this->assertSame(['описание'], $this->client->enumMeta($nsName));

        // reading a deleted key yields an empty string, not an error (v5.15.0)
        $this->assertSame('', $this->client->getMeta($nsName, 'schema_version'));
    }

    public function testPutMetaOverwritesValue(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_meta_ow');

        $this->client->putMeta($nsName, 'k', 'v1');
        $this->client->putMeta($nsName, 'k', 'v2');

        $this->assertSame('v2', $this->client->getMeta($nsName, 'k'));
    }

    public function testSetSchemaAndGetProtobufSchema(): void
    {
        $nsName = $this->createNamespaceWithPk('grpc_schema');
        $this->client->setSchema($nsName, [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'number']],
            'additionalProperties' => false,
        ]);

        $proto = $this->client->getProtobufSchema([$nsName]);

        $this->assertStringContainsString('syntax = "proto3"', $proto);
        $this->assertStringContainsString('message', $proto);
        $this->assertStringContainsString('id', $proto);
    }

    public function testAddNamespaceCreatesNamespaceWithIndexes(): void
    {
        $nsName = $this->uniqueNs('grpc_addns');

        $this->client->addNamespace($nsName, [], [
            ['name' => 'id', 'fieldType' => 'int', 'indexType' => 'hash', 'isPk' => true],
            ['name' => 'rank', 'fieldType' => 'int', 'indexType' => 'tree'],
        ]);

        $this->assertContains($nsName, $this->client->enumNamespaces());

        // both indexes are usable right away
        $this->client->modifyItems($nsName, [
            ['id' => 1, 'rank' => 20],
            ['id' => 2, 'rank' => 10],
        ]);
        $items = iterator_to_array(
            $this->client->execSql(sprintf('SELECT * FROM %s WHERE rank > 5 ORDER BY rank', $nsName)),
            false
        );
        $this->assertSame([10, 20], array_column($items, 'rank'));
    }
}
